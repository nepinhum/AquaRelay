<?php

namespace aquarelay\plugin\loader;

use aquarelay\ProxyServer;
use aquarelay\plugin\Plugin;
use aquarelay\plugin\PluginDescription;
use aquarelay\plugin\PluginException;
use Symfony\Component\Yaml\Yaml;

use function class_exists;
use function file_exists;
use function is_subclass_of;

readonly class PharPluginLoader implements PluginLoaderInterface
{
	public function __construct(
		private ProxyServer $server,
		private string      $dataPath
	) {}

	public function canLoad(string $path) : bool
	{
		return is_file($path) && str_ends_with($path, '.phar');
	}

	public function load(string $path) : ?Plugin
	{
		$pluginYml = "phar://{$path}/plugin.yml";
		if (!file_exists($pluginYml)) {
			return null;
		}

		$data = Yaml::parse(file_get_contents($pluginYml));
		$description = PluginDescription::fromYaml($data);

		$vendor = "phar://{$path}/vendor/autoload.php";
		if (file_exists($vendor)) {
			require_once $vendor;
		}

		$main = $description->getMain();
		$mainFile = "phar://{$path}/src/" . str_replace('\\', '/', $main) . '.php';
		if (file_exists($mainFile)) {
			require_once $mainFile;
		}

		if (!class_exists($main)) {
			throw new PluginException("Main class {$main} not found in phar");
		}

		if (!is_subclass_of($main, Plugin::class)) {
			throw new PluginException("Main class must extend Plugin");
		}

		$plugin = new $main();
		$plugin->setDescription($description);
		$plugin->setServer($this->server);
		$plugin->setDataFolder($this->dataPath . DIRECTORY_SEPARATOR . $description->getName());
		$plugin->onLoad();

		return $plugin;
	}
}