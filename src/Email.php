<?php namespace Model\Email;

use Model\Config\Config;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
	protected array $options;
	protected PHPMailer $message;
	protected bool $main_address_set = false;

	public function __construct(string $subject, string $text, array $options = [])
	{
		$this->options = array_merge($this->getConfig(), $options);

		// Backward compatibility
		if (isset($this->options['from_mail'])) {
			$this->options['from'] = [
				'mail' => $this->options['from_mail'],
				'name' => $this->options['from_name'],
			];
		}

		$this->message = new PHPMailer(true);
		if ($this->options['smtp']) {
			$this->message->IsSMTP();
			$this->message->SMTPOptions = [
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true,
				],
			];
			if ($this->options['encryption'])
				$this->message->SMTPSecure = $this->options['encryption'];
			$this->message->Host = $this->options['smtp'];
			$this->message->Port = $this->options['port'];
			if ($this->options['username']) {
				$this->message->SMTPAuth = true;
				$this->message->Username = $this->options['username'];
				if ($this->options['password'])
					$this->message->Password = $this->options['password'];
			}
			if ($this->options['debug'])
				$this->message->SMTPDebug = 2;
		}

		$this->message->IsHTML();
		$this->message->CharSet = $this->options['charset'];

		$this->message->setFrom($this->options['from']['mail'], $this->options['from']['name']);
		if (isset($this->options['reply_to']))
			$this->message->AddReplyTo($this->options['reply_to']['mail'], $this->options['reply_to']['name']);
		else
			$this->message->AddReplyTo($this->options['from']['mail'], $this->options['from']['name']);

		$this->message->Subject = $subject;
		$this->setText($text);
	}

	public function __destruct()
	{
		unset($this->message);
	}

	private function setText(string $text)
	{
		$text = $this->options['header'] . $text . $this->options['footer'];
		$this->message->Body = $text;
		$plain = html_entity_decode(trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/si', '', $text))), ENT_QUOTES, 'utf-8');
		$this->message->AltBody = $plain;
	}

	public function attachFile(string $path, string $name)
	{
		$this->message->AddAttachment($path, $name);
	}

	public function send(string|array $emails)
	{
		if (!is_array($emails))
			$emails = [$emails];

		foreach ($emails as $email) {
			if (!$this->main_address_set) {
				$this->message->AddAddress($email);
				$this->main_address_set = true;
			} else {
				$this->message->AddBCC($email);
			}
		}

		if (!$this->message->Send())
			throw new \Exception($this->message->ErrorInfo);

		if ($this->options['smtp'])
			$this->message->SmtpClose();
	}

	private function getConfig(): array
	{
		return Config::get('email', [
			'version' => '0.2.0',
			'migration' => function () {
				if (defined('INCLUDE_PATH') and file_exists(INCLUDE_PATH . 'app/config/Email/config.php')) {
					// ModEl 3 migration
					require(INCLUDE_PATH . 'app/config/Email/config.php');

					$config['from'] = [
						'mail' => $config['from_mail'],
						'name' => $config['from_name'],
					];

					unset($config['from_mail']);
					unset($config['from_name']);

					return $config;
				}

				return [
					'from' => [
						'mail' => '',
						'name' => defined('APP_NAME') ? APP_NAME : '',
					],
					'smtp' => false,
					'port' => 25,
					'header' => '<div style="width: 800px; margin: auto"><p style="text-align: center"><img src="https://' . $_SERVER['HTTP_HOST'] . (defined('PATH') ? PATH : '/') . 'app/assets/img/logo.png" alt="" /></p>',
					'footer' => '</div>',
					'debug' => false,
					'username' => null,
					'password' => null,
					'encryption' => null,
					'charset' => 'UTF-8',
				];
			},
		]);
	}
}
