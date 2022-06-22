<?php

namespace Jasny\Controller\Parameter;

trait SingleParameter
{
    static public array $types = [
        'bool' => [FILTER_VALIDATE_BOOL],
        'int' => [FILTER_VALIDATE_INT],
        'float' => [FILTER_VALIDATE_FLOAT],
        'email' => [FILTER_VALIDATE_EMAIL],
        'url' => [FILTER_VALIDATE_URL],
    ];

    public ?string $key;
    public ?string $type;

    public function __construct(?string $key = null, ?string $type = null)
    {
        if ($type !== null && !isset(self::$types[$type])) {
            throw new \DomainException("Undefined parameter type '$type'");
        }

        $this->key = $key;
        $this->type = $type;
    }

    /**
     * Apply sanitize filter to value.
     */
    protected function filter(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        [$filter, $options] = (self::$types[$type] ?? []) + [FILTER_DEFAULT, 0];

        return filter_var($value, $filter, $options);
    }
}