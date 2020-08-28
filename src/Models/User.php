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

    /**
     * @param array{id?: string, displayName?: string, mail?: string, accountEnabled?: bool} $data
     */
    public static function fromArray(array $data) : self {
        if (!array_key_exists('id', $data)) {
            throw new InvalidArgumentException('Missing data element: id');
        } else if (!array_key_exists('displayName', $data)) {
            throw new InvalidArgumentException('Missing data element: displayName');
        } else if (!array_key_exists('mail', $data)) {
            throw new InvalidArgumentException('Missing data element: mail');
        } else if (!array_key_exists('accountEnabled', $data)) {
            throw new InvalidArgumentException('Missing data element: accountEnabled');
        }

        return new static(
            (string) $data['id'],
            (string) $data['displayName'],
            (string) $data['mail'],
            (bool) $data['accountEnabled'],
        );
    }
}
