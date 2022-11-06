<?php namespace Model\Email;

use Model\Config\AbstractConfigProvider;

class ConfigProvider extends AbstractConfigProvider
{
	public static function migrations(): array
	{
		return [
			[
				'version' => '0.2.0',
				'migration' => function (array $currentConfig, string $env) {
					if ($currentConfig) // Already existing
						return $currentConfig;

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
			],
		];
	}
}
