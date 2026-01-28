<?php

namespace aquarelay\plugin\loader;

use aquarelay\plugin\Plugin;
use aquarelay\plugin\PluginException;

interface PluginLoaderInterface
{
	/**
	 * @param string $path
	 * @return bool
	 */
	public function canLoad(string $path) : bool;

	/**
	 * @throws PluginException
	 */
	public function load(string $path) : ?Plugin;
}