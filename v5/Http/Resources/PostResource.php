<?php
namespace v5\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Ushahidi\Core\Entity\Post;
use v5\Models\Post\Post as v5Post;

class PostResource extends BaseResource
{
    public static $wrap = 'result';
    /*
         * @param  \Illuminate\Http\Request  $request
         * @return array
         */
    private function includeResourceFields($request)
    {
        return self::includeFields($request, [
            'id',
            'form_id',
            'user_id',
            'type',
            'title',
            'slug',
            'content',
            'author_email',
            'author_realname',
            'status',
            'published_to',
            'locale',
            'created',
            'updated',
            'post_date',
//            'base_language' => $this->base_language,
//            'translations' => new TranslationCollection($this->translations),
//            'enabled_languages' => [
//                'default'=> $this->base_language,
//                'available' => $this->translations->groupBy('language')->keys()
//            ],
        ]);
    }
    private function getResourcePostContent()
    {

        $values = $this->getPostValues();
        $col = new Collection(['values' => $values, 'tasks' => $this->survey ? $this->survey->tasks : []]);
        $no_values = false;

        if ($values->count() === 0) {
            $no_values = true;
        }
        $post_content =  Collection::make([]);

        if ($no_values && $this->survey) {
            $post_content = new TaskCollection($this->survey->tasks);
        } elseif ($this->survey) {
            $post_content = new PostValueCollection($col);
        }
        return $post_content;
    }

    private function getResourcePrivileges()
    {
        $authorizer = service('authorizer.post');
        $entity = new Post($this->resource->toArray());
        // if there's no user the guards will kick them off already, but if there
        // is one we need to check the authorizer to ensure we don't let
        // users without admin perms create forms etc
        // this is an unfortunate problem with using an old version of lumen
        // that doesn't let me do guest user checks without adding more risk.
        return $authorizer->getAllowedPrivs($entity);
    }
    private function hydrateResourceRelationships($request)
    {
        $hydrate = $this->getHydrate(v5Post::$relationships, $request);
        $result = [];
        foreach ($hydrate as $relation) {
            switch ($relation) {
                case 'categories':
                    $result['categories'] = $this->categories;
                    break;
                case 'completed_stages':
                    $result['completed_stages'] = $this->postStages;
                    break;
                case 'post_content':
                    $result['post_content'] = $this->getResourcePostContent();
                    break;
            }
        }
        return $result;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fields = $this->includeResourceFields($request);
        $result = $this->setResourceFields($fields);
        $hydrated = $this->hydrateResourceRelationships($request);
        $allowed_privs = ['allowed_privileges' => $this->getResourcePrivileges()];
        return array_merge($result, $hydrated, $allowed_privs, [
            'translations' => new TranslationCollection($this->translations),
            'enabled_languages' => [
                'default'=> $this->base_language,
                'available' => $this->translations->groupBy('language')->keys()
            ]
        ]);
    }
}
