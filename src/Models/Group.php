<?php declare(strict_types=1);
namespace NAVIT\AzureAd\Models;

use InvalidArgumentException;

class Group extends Model {
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $displayName;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $mailNickname;

    final public function __construct(string $id, string $displayName, string $description, string $mailNickname) {
        $this->id           = $id;
        $this->displayName  = $displayName;
        $this->description  = $description;
        $this->mailNickname = $mailNickname;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getDisplayName() : string {
        return $this->displayName;
    }

    public function getDescription() : string {
        return $this->description;
    }

    public function getMailNickname() : string {
        return $this->mailNickname;
    }

    /**
     * @param array{id?: string, displayName?: string, description?: string, mailNickname?: string} $data
     */
    public static function fromArray(array $data) : self {
        if (!array_key_exists('id', $data)) {
            throw new InvalidArgumentException('Missing data element: id');
        } else if (!array_key_exists('displayName', $data)) {
            throw new InvalidArgumentException('Missing data element: displayName');
        } else if (!array_key_exists('description', $data)) {
            throw new InvalidArgumentException('Missing data element: description');
        } else if (!array_key_exists('mailNickname', $data)) {
            throw new InvalidArgumentException('Missing data element: mailNickname');
        }

        return new static(
            (string) $data['id'],
            (string) $data['displayName'],
            (string) $data['description'],
            (string) $data['mailNickname']
        );
    }
}