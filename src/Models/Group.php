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

    public static function fromArray(array $data) : self {
        foreach (['id', 'displayName', 'description', 'mailNickname'] as $required) {
            if (empty($data[$required])) {
                throw new InvalidArgumentException(sprintf('Missing data element: %s', $required));
            }
        }

        return new static(
            $data['id'],
            $data['displayName'],
            $data['description'],
            $data['mailNickname']
        );
    }
}