<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use InvalidArgumentException;

class User extends Model {
    /** @var string */
    private $id;

    /** @var string */
    private $displayName;

    /** @var string */
    private $mail;

    /** @var bool */
    private $accountEnabled;

    final public function __construct(string $id, string $displayName, string $mail, bool $accountEnabled) {
        $this->id             = $id;
        $this->displayName    = $displayName;
        $this->mail           = $mail;
        $this->accountEnabled = $accountEnabled;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getDisplayName() : string {
        return $this->displayName;
    }

    public function getMail() : string {
        return $this->mail;
    }

    public function getAccountEnabled() : bool {
        return $this->accountEnabled;
    }

    public static function fromArray(array $data) : self {
        foreach (['id', 'displayName', 'mail', 'accountEnabled'] as $required) {
            if (!array_key_exists($required, $data)) {
                throw new InvalidArgumentException(sprintf('Missing data element: %s', $required));
            }
        }

        return new static(
            (string) $data['id'],
            (string) $data['displayName'],
            (string) $data['mail'],
            (bool) $data['accountEnabled'],
        );
    }
}
