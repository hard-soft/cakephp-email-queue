<?php
namespace EmailQueue\Database\Type;

use Cake\Database\Driver;
use Cake\Database\Type\BaseType;
use Cake\Database\DriverInterface;

class SerializeType extends BaseType {
	/**
	 * Creates a PHP value from a stored representation
	 *
	 * @param mixed $value to unserialize
	 * @param Driver $driver database driver
	 * @return mixed|null|string|void
	 */
	public function toPHP($value, DriverInterface $driver) {
		if ($value === null) {
			return null;
		}

		return unserialize($value);
	}

	/**
	 * Generates a storable representation of a value
	 *
	 * @param mixed $value to serialize
	 * @param Driver $driver database driver
	 * @return null|string
	 */
	public function toDatabase($value, DriverInterface $driver) {
		return serialize($value);
	}

	public function marshal($value) {
		return $value;
	}

	/**
	 * Returns whether the cast to PHP is required to be invoked
	 *
	 * @return bool always true
	 */
	public function requiresToPhpCast(): bool {
		return true;
	}
}
