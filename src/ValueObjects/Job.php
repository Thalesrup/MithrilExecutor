<?php
declare(strict_types=1);

namespace MithrilExecutor\ValueObjects;

final class Job
{
    public function __construct(
        public readonly string $id,
        public readonly string $className,
        public readonly array $constructorArgs,
        /** @var array<int, array{method:string,args:array}> */
        public readonly array $calls,
        public readonly int $createdAtMs,
        public readonly ?string $meta = null,
    ) {}

    public static function newId(): string
    {
        $rand = bin2hex(random_bytes(6));
        return date('YmdHis') . '-' . $rand;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (string)($data['id'] ?? self::newId()),
            (string)($data['className'] ?? ''),
            (array)($data['constructorArgs'] ?? []),
            (array)($data['calls'] ?? []),
            (int)($data['createdAtMs'] ?? (int) floor(microtime(true) * 1000)),
            isset($data['meta']) ? (string)$data['meta'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'className' => $this->className,
            'constructorArgs' => $this->constructorArgs,
            'calls' => $this->calls,
            'createdAtMs' => $this->createdAtMs,
            'meta' => $this->meta,
        ];
    }
}
