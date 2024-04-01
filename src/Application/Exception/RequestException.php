<?php

namespace Pantono\Core\Application\Exception;

use JsonException;

class RequestException extends \RuntimeException implements \Throwable
{
    private array $fields;
    /**
     * @var string
     */
    protected $message;
    /**
     * @var int
     */
    protected $code;

    public function __construct(string $message, array $fields = [], int $code = 400)
    {
        $this->message = $message;
        $this->fields = $fields;
        $this->code = $code;
        parent::__construct($message, $code);
    }

    public function __toString(): string
    {
        return $this->getMessageAsString();
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    private function getMessageAsString(): string
    {
        $data = [
            'success' => false,
            'error' => [
                'message' => $this->message,
            ]
        ];
        if (!empty($this->fields)) {
            $data['error']['fields'] = $this->fields;
        }
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return '{"success":false,"error":{"message":"' . $this->message . '"}}';
        }
    }
}
