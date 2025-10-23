<?php
/**
 * SLRResult - Result object for SLR operations
 * Provides better error handling than returning false
 */

namespace App\Services\SLR;

class SLRResult {
    private bool $success;
    private mixed $data;
    private string $errorMessage;
    private ?string $errorCode;
    
    private function __construct(bool $success, mixed $data = null, string $errorMessage = '', ?string $errorCode = null) {
        $this->success = $success;
        $this->data = $data;
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode;
    }
    
    /**
     * Create a successful result
     * @param mixed $data Result data
     * @return self
     */
    public static function success(mixed $data = null): self {
        return new self(true, $data);
    }
    
    /**
     * Create a failed result
     * @param string $errorMessage Error message
     * @param string|null $errorCode Error code
     * @return self
     */
    public static function failure(string $errorMessage, ?string $errorCode = null): self {
        return new self(false, null, $errorMessage, $errorCode);
    }
    
    /**
     * Check if operation was successful
     * @return bool
     */
    public function isSuccess(): bool {
        return $this->success;
    }
    
    /**
     * Check if operation failed
     * @return bool
     */
    public function isFailure(): bool {
        return !$this->success;
    }
    
    /**
     * Get result data
     * @return mixed
     */
    public function getData(): mixed {
        return $this->data;
    }
    
    /**
     * Get error message
     * @return string
     */
    public function getErrorMessage(): string {
        return $this->errorMessage;
    }
    
    /**
     * Get error code
     * @return string|null
     */
    public function getErrorCode(): ?string {
        return $this->errorCode;
    }
    
    /**
     * Get data or throw exception on failure
     * @return mixed
     * @throws \Exception
     */
    public function getDataOrThrow(): mixed {
        if ($this->isFailure()) {
            throw new \Exception($this->errorMessage);
        }
        return $this->data;
    }
    
    /**
     * Get data or return default value on failure
     * @param mixed $default
     * @return mixed
     */
    public function getDataOr(mixed $default): mixed {
        return $this->isSuccess() ? $this->data : $default;
    }
}
