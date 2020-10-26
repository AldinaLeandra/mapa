<?php

namespace v5\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Ushahidi\Core\Entity\Permission;
use Illuminate\Support\Facades\Input;
use v5\Models\Scopes\CategoryAllowed;

class Category extends BaseModel
{
    public $errors;
    /**
     * Add eloquent style timestamps
     *
     * @var boolean
     */
    public $timestamps = false;

    /**
     * Specify the table to load with Survey
     *
     * @var string
     */
    protected $table = 'tags';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var  array
     */
    protected $hidden = [
        'description',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created'
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_id',
        'tag',
        'slug',
        'type',
        'color',
        'icon',
        'description',
        'role',
        'priority',
        'base_language'
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'category'
    ];

    protected $casts = [
        'role' => 'json'
    ];

    /**
     * Add relations to eager load
     *
     * @var string[]
     */
    protected $with = ['translations'];
    protected $translations;
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function validationMessages()
    {
        return [
            'parent_id.exists' => trans(
                'validation.exists',
                ['field' => trans('fields.parent_id')]
            ),
            'tag.required'      => trans(
                'validation.not_empty',
                ['field' => trans('fields.tag')]
            ),
            'tag.unique'      => trans(
                'validation.unique',
                ['field' => trans('fields.tag')]
            ),
            'tag.min'           => trans(
                'validation.min_length',
                [
                    'param2' => 2,
                    'field'  => trans('fields.tag'),
                ]
            ),
            'tag.max'           => trans(
                'validation.max_length',
                [
                    'param2' => 255,
                    'field'  => trans('fields.tag'),
                ]
            ),
            'tag.regex'         => trans(
                'validation.regex',
                ['field' => trans('fields.tag')]
            ),
            'slug.required'     => trans(
                'validation.not_empty',
                ['field' => trans('fields.slug')]
            ),
            'slug.min'          => trans(
                'validation.min_length',
                [
                    'param2' => 2,
                    'field'  => trans('fields.slug'),
                ]
            ),
            'slug.unique'      => trans(
                'validation.unique',
                ['field' => trans('fields.slug')]
            ),
            'type.required'     => trans(
                'validation.not_empty',
                ['field' => trans('fields.type')]
            ),
            'type.in'           => trans(
                'validation.in_array',
                ['field' => trans('fields.type')]
            ),
            'description.regex' => trans(
                'validation.regex',
                ['field' => trans('fields.description')]
            ),

            'description.min' => trans(
                'validation.min_length',
                ['field' => trans('fields.description')]
            ),

            'description.max' => trans(
                'validation.max_length',
                ['field' => trans('fields.description')]
            ),
            'icon.regex'        => trans(
                'validation.regex',
                ['field' => trans('fields.icon')]
            ),
            'priority.numeric'  => trans(
                'validation.numeric',
                ['field' => trans('fields.priority')]
            ),
        ];
    }//end translations()

    /**
     * Return all validation rules
     * @return array
     */
    public function getRules()
    {
        return [
             'parent_id' => 'nullable|sometimes|exists:tags,id',
             'tag'         => [
                'required',
                'min:2',
                'max:255',
                'regex:/^[\pL\pN\pP ]++$/uD',
                Rule::unique('tags')->ignore($this->id)
             ],
             'slug'        => [
                'required',
                'min:2',
                Rule::unique('tags')->ignore($this->id)
             ],
             'type'        => [
                'required',
                Rule::in([
                    'category',
                    'status'
                ])
             ],
             'description' => [
                 'min:2',
                 'max:255'
             ],
             'color'                             => [
                 'string',
                 'nullable',
             ],
             'icon'        => [
                'regex:/^[\pL\s\_\-]++$/uD'
             ],
             'priority'    => [
                'numeric'
             ],
             'role' => [
                function ($attribute, $value, $fail) {
                    $has_parent = Input::get('parent_id'); // Retrieve status

                    $parent = $has_parent ? Category::find(Input::get('parent_id')) : null;
                    // ... and check if the role matches its parent
                    if ($parent && $parent->role != $value) {
                        return $fail(trans('validation.child_parent_role_match'));
                    }
                }
             ]
        ];
    }//end validationMessages()

    /**
     * Get the category's translation.
     */
    public function translations()
    {
        return $this->morphMany('v5\Models\Translation', 'translatable');
    }//end getRules()

    public function parent()
    {
        return $this->hasOne('v5\Models\Category', 'id', 'parent_id');
    }
    public function children()
    {
        return $this->hasMany('v5\Models\Category', 'parent_id', 'id');
    }


    /**
     * Get the category's color format
     *
     * @param  string  $value
     * @return void
     */
    public function getColorAttribute($value)
    {
        return $value ? "#" . $value : $value;
    }
    /**
     * Set the category's color format
     *
     * @param  string  $value
     * @return void
     */
    public function setColorAttribute($value)
    {
        if (isset($value)) {
            $this->attributes['color'] = ltrim($value, '#');
        }
    }

    public function validate($data = [])
    {
        $v = Validator::make($data, $this->getRules(), $this->validationMessages());
        $v->sometimes('role', 'exists:roles,name', function ($input) {
            return !!$input->get('role');
        });
        // check for failure
        if (!$v->fails()) {
            return true;
        }
        // set errors and return false
        $this->errors = $v->errors();
        return false;
    }

    public function errors()
    {
        return $this->errors;
    }
}//end class
