<?php declare(strict_types = 1);

namespace App\Exchange\Trade\Config;

use ArrayAccess;

class OrderFilterConfig implements ArrayAccess
{
    /** @var mixed[] */
    private $defaultOptions = [
        'start_time' => 0,
        'end_time' => 0,
        'offset' => 0,
        'limit' => 100,
        'side' => 'all',
    ];

    public function merge(array $options): self
    {
        $validOptions = array_filter($options, static function ($value) {
            return null !== $value;
        });
        $this->defaultOptions = array_merge($this->defaultOptions, $validOptions);

        return $this;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->defaultOptions[] = $value;
        } else {
            $this->defaultOptions[$offset] = $value;
        }
    }

    /** @param mixed $offset */
    public function offsetExists($offset): bool
    {
        return isset($this->defaultOptions[$offset]);
    }

    /** @param mixed $offset */
    public function offsetUnset($offset): void
    {
        unset($this->defaultOptions[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->defaultOptions[$offset] ?? null;
    }
}
