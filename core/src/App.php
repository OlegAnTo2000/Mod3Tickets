<?php

namespace Tickets;

use DI\Container;
use MODX\Revolution\modX;
use MODX\Revolution\Processors\ProcessorResponse;
use Psr\Container\ContainerInterface;

class App
{
	public const NAME             = 'tickets';
	public const VENDOR_NAME      = 'oleganto2000/tickets';
	public const NAMESPACE_PREFIX = 'Tickets';

	public const VENDOR_PATH = MODX_CORE_PATH . 'vendor/' . self::VENDOR_NAME;
	public const CORE_PATH   = MODX_CORE_PATH . 'components/' . self::NAME;
	public const ASSETS_PATH = MODX_ASSETS_PATH . 'components/' . self::NAME;

	protected modX $modx;
	protected static ContainerInterface $container;

	public function __construct(modX $modx)
	{
		$this->modx = $modx;
		$container = new Container();
		$container->set(modX::class, $this->modx);
		$container->set('modx', $this->modx);
		static::$container = $container;
	}

	public static function getContainer(): ContainerInterface
	{
		return static::$container;
	}

	public static function prepareLexicon(array $arr, string $prefix = ''): array
	{
		$out = [];
		foreach ($arr as $k => $v) {
			$key = !$prefix ? $k : "{$prefix}.{$k}";
			if (is_array($v)) {
				$out += self::prepareLexicon($v, $key);
			} else {
				$out[$key] = $v;
			}
		}
		return $out;
	}

	public function getLexicon(string $locale = 'en', $prefixes = []): array
	{
		$namespace = $this::NAME;
		$this->modx->lexicon->load($locale . ':' . $namespace . ':default');
		$entries = [];

		if ($prefixes) {
			if (!is_array($prefixes)) $prefixes = [$prefixes];
			foreach ($prefixes as $prefix) {
				$entries += $this->modx->lexicon->fetch($namespace . '.' . $prefix);
			}
		} else {
			$entries = $this->modx->lexicon->fetch($namespace);
		}

		$keys = array_map(static function ($key) use ($namespace) {
			return str_replace($namespace . '.', '', $key);
		}, array_keys($entries));

		return array_combine($keys, array_values($entries));
	}

	public static function getErrorMessage(ProcessorResponse $response): string
	{
		if (!$message = $response->getMessage()) {
			$message = 'errors.unknown';
			if ($response->hasFieldErrors() && $errors = $response->getFieldErrors()) {
				$message = current($errors)->message;
			}
		}
		return $message;
	}
}
