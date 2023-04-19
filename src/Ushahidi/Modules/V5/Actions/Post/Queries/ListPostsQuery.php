<?php

namespace Ushahidi\Modules\V5\Actions\Post\Queries;

use App\Bus\Query\Query;
use Ushahidi\Modules\V5\Models\Post\Post;
use Illuminate\Http\Request;
use Ushahidi\Modules\V5\DTO\Paging;
use Ushahidi\Modules\V5\DTO\PostSearchFields;

class ListPostsQuery implements Query
{
    //private const DEFAULT_LIMIT = 20;

    /**
     * @var Paging
     */
    private $paging;
    private $search_fields;

    // private const ALLOWED_FIELDS = [
    //     'id',
    //     'parent_id',
    //     'base_language',
    //     'form_id',
    //     'status',
    //     'form_id',
    //     'user_id',
    //     'type',
    //     'title',
    //     'slug',
    //     'content',
    //     'author_email',
    //     'author_realname',
    //     'status',
    //     'published_to',
    //     'locale',
    //     'post_date',
    //     'base_language',
    //     'created',
    //     'updated'
    // ];
    /**
     * @var array
     */
    private $fields;
    private $hydrates;

    private $with_relationships;

    private $fields_for_relationships;
    /**
     * @var int
     */
    private $limit;

    private function __construct(
        Paging $paging,
        PostSearchFields $search_fields,
        array $fields = [],
        array $hydrates = []
    ) {
        $this->paging = $paging;
        $this->search_fields = $search_fields;

        $this->fields = $fields;
        $this->limit = 20;
        $this->fields = array_unique(array_merge($fields, Post::REQUIRED_FIELDS));
        $this->hydrates = $hydrates;
        $this->with_relationships = [];
        $this->fields_for_relationships = [];
        foreach ($hydrates as $hydrate) {
            if (Post::ALLOWED_RELATIONSHIPS[$hydrate]) {
                $this->with_relationships = array_unique(
                    array_merge(
                        $this->with_relationships,
                        Post::ALLOWED_RELATIONSHIPS[$hydrate]['relationships']
                    )
                );
                $this->fields_for_relationships = array_unique(
                    array_merge(
                        $this->fields_for_relationships,
                        Post::ALLOWED_RELATIONSHIPS[$hydrate]['fields']
                    )
                );
            }
        }
    }

    /**
     * The data parameter is an array containing two keys
     * fields: an array of fields to be returned
     * limit: the number of posts to be returned
     *
     * "fields" key is optional, if not provided, all fields will be returned,
     * if provided it must be an array of strings and must be a subset of the ALLOWED_FIELDS
     *
     * "limit" key is optional, if not provided, the default limit of DEFAULT_LIMIT will be used.
     * if provided it must be an integer greater than 0
     *
     * @param array $data
     * @return static
     */
    // public static function fromArray(array $data): self
    // {
    //     if (array_key_exists('fields', $data)) {
    //         $fields = array_filter($data['fields'], function ($field) {
    //             return in_array($field, self::ALLOWED_FIELDS);
    //         });

    //         if (count($fields) !== count($data['fields'])) {
    //             throw new \InvalidArgumentException('Invalid fields provided');
    //         }
    //     }

    //     $fields = $data['fields'] ?? [];

    //     if (array_key_exists('limit', $data) && $data['limit'] < 1) {
    //         throw new \InvalidArgumentException('Limit must be greater than 0');
    //     }

    //     $limit = $data['limit'] ?? self::DEFAULT_LIMIT;

    //     return new self($fields, $limit);
    // }

    public function getPaging(): Paging
    {
        return $this->paging;
    }

    public function getSearchFields()
    {
        return $this->search_fields;
    }

    public static function fromRequest(Request $request): self
    {

        // do we need to throw execption if send an field not found ?!
        if ($request->get('format') === 'minimal') {
            $fields = ['id', 'title', 'content'];
            $hydrates = ['translations'];
        } elseif (!$request->get('only')) {
            $fields = Post::ALLOWED_FIELDS;
            $hydrates = array_keys(Post::ALLOWED_RELATIONSHIPS);
        } else {
            $only_values = explode(',', $request->get('only'));
            $fields = [];
            $hydrates = [];
            foreach ($only_values as $only_value) {
                if (in_array($only_value, Post::ALLOWED_FIELDS)) {
                    $fields[] = $only_value;
                } elseif (array_key_exists($only_value, Post::ALLOWED_RELATIONSHIPS)) {
                    $hydrates[] = $only_value;
                }
            }
        }

        return new self(Paging::fromRequest($request), new PostSearchFields($request), $fields, $hydrates);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }


    public function getFields(): array
    {
        return $this->fields;
    }

    public function getHydrates(): array
    {
        return $this->hydrates;
    }

    public function getWithRelationship(): array
    {
        return $this->with_relationships;
    }

    public function getFieldsForRelationship(): array
    {
        return $this->fields_for_relationships;
    }
}
