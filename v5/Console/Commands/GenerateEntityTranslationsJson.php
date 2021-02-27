<?php
/**
 * *
 *  * Ushahidi Generate Entity Translations JSON files
 *  *
 *  * @author     Ushahidi Team <team@ushahidi.com>
 *  * @package    Ushahidi\Application
 *  * @copyright  2020 Ushahidi
 *  * @license    https://www.gnu.org/licenses/agpl-3.0.html GNU Affero General Public License Version 3 (AGPL3)
 *
 *
 */

namespace v5\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\Util\MimeType;
use Ushahidi\App\Tools\OutputText;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Ushahidi\Core\Tool\FileData;
use v5\Models\Attribute;
use v5\Models\Category;
use v5\Models\Post\Post;
use v5\Models\Stage;
use v5\Models\Survey;

class GenerateEntityTranslationsJson extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'entitytranslations:out';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a JSON file per language with all entity source texts.';
    protected $signature = 'entitytranslations:out';
    protected $batchStamp;
    protected $addPrivateResponses = false;
    protected $addUnpublishedPosts = false;

    protected $exportSurveys = [];
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        if (!$this->confirm("[Warning] This is an ALPHA Cli feature. Do you want to continue?")) {
            $this->info("Export process cancelled");
            return;
        }
        $warn = "[Data warning] Should we add private responses? Private responses may contain sensitive data.";
        if ($this->confirm($warn)) {
            $this->addPrivateResponses = true;
        }
        $warn = "[Data warning] Should we add non-public posts? This includes in-review and archived posts.";
        if ($this->confirm($warn)) {
            $this->addUnpublishedPosts = true;
        }

        $survey = Survey::all(["id", "name"])->mapWithKeys(function ($item) {
            return [$item["id"] => $item["name"] . ":" . $item["id"]];
        })->toArray();
        $all = $this->confirm("Do you want to export ALL surveys?");

        if (!$all) {
            $response = $this->choice("Which surveys should we include?", $survey, null, null, true);
            $this->exportSurveys = array_map(function ($item) {
                $del = ":";
                $exp = explode($del, $item);
                return trim(array_pop($exp));
            }, $response);
        }

        $this->batchStamp = Carbon::now()->format('Ymdhms');
        $this->makeSurveyEntities();
        $this->generatePostEntities();
        $this->generateCategoryEntities();
    }

    /**
     * @return mixed
     */
    private function getCategories()
    {
        return
            Category::all(
                array_merge(['id', 'base_language'], Category::translatableAttributes())
            )
                ->makeHidden(['role', 'parent', 'translations'])
                ->groupBy('base_language');
    }

    /**
     * Translatable items are the basic structure of each row to translate.
     * For each object's translatable attribute, we get its structure to use when creating the JSON output.
     * @param $item
     * @param $output_type
     * @param $context
     * @param $translatable_field
     * @return array|void
     */
    private function makeTranslatableItem($item, $output_type, $context, $translatable_field)
    {
        $to_translate = $item->$translatable_field;
        if ($to_translate === null || $to_translate === "") {
            return false;
        }
        if (is_array($to_translate) && count($to_translate) === 0) {
            return false;
        }
        if (is_array($to_translate) || is_object($to_translate)) {
            $to_translate = json_encode($to_translate);
        }
        return [
            // the item id, to be used when importing
            "id" => $item->id,
            // the language we are translating from
            "base_language" => $item->base_language,
            // the field name we want to translate
            "attribute_name" => $translatable_field,
            // the content of the field we want to translate
            "to_translate" => $to_translate,
            // this field remains empty since it's what the translator will use to create a translation
            "translation" => $to_translate,
            // output_type is used when importing to know what we need to save
            "output_type"=> $output_type,
            // context is just there to help translators understand more of what they are doing
            "context"   => $context
        ];
    }

    /**
     * Creates JSON file with the entities relating to Categories, to be used by translators in their systems
     */
    protected function generateCategoryEntities()
    {
        $attributes = Collection::make(Category::translatableAttributes());
        $categoriesByLang = $this->getCategories();
        $categoriesByLang->each(function ($categories, $language) use ($attributes) {
            $items = Collection::make([]);
            $categories->each(function ($category) use ($attributes, $language, &$items) {
                $attributes->each(function ($tr) use ($category, &$items) {
                    $toSave = $this->makeTranslatableItem($category, 'category', "Category", $tr);
                    if ($toSave) {
                        $items->push($toSave);
                    }
                });
            });
            /**
             * Generates a file per each language containing
             * all the survey related entities base text
             */
            $this->generateFile($items->toJson(), $language, 'categories');
            echo OutputText::success("Created file for categories - based on language: $language.");
            echo OutputText::info("Total entities to translate: " . $items->count());
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection|Collection|Post[]
     */
    private function getPosts()
    {
        echo OutputText::info("Gathering posts");

        $posts = null;

        $posts =  Post::query();
        if (!$this->addUnpublishedPosts) {
            $posts = $posts->where('status', '=', 'published');
        }
        if (!empty($this->exportSurveys)) {
            $posts = $posts->whereIn('form_id', $this->exportSurveys);
        }
        //@NOTE: UTF8 support is flaky for post values. Check arabic.
        //@NOTE: this is an alpha override
        $posts = $posts->get(
            array_merge(['id', 'base_language', 'status', 'form_id'], Post::translatableAttributes())
        )->load('survey');

        return $posts
            ->makeHidden(['values', 'translations'])
            ->map(function ($post) {

                $values = $post->getTranslatablePostValues($this->addPrivateResponses)
                    ->map(function ($value) use ($post) {
                        return $this->attachProperties($value, [
                            'base_language' => $post->survey ? $post->survey->base_language : $post->base_language,
                            'output_type' => 'post_value',
                            'post_id' => $post->id,
                            'attribute_name' => $value->attribute->label
                        ])
                                ->makeHidden(['post', 'translations' , 'attribute']);
                    });
                return $this->attachProperties($post, [
                    'base_language' => $post->survey ? $post->survey->base_language : $post->base_language,
                    'output_type' => 'post',
                    'fieldValues' => $values
                ]);
            })->groupBy('base_language');
    }

    /**
     * Creates JSON file with the entities relating to Posts, to be used by translators in their systems
     */
    protected function generatePostEntities()
    {
        $attributes = Collection::make(Post::translatableAttributes());
        $postsByLang = $this->getPosts();
        if (!$postsByLang) {
            return;
        }
        $postsByLang->each(function ($posts, $language) use ($attributes) {
            if (!$language) {
                return;
            }
            $items = Collection::make([]);
            $posts->each(function ($post) use ($attributes, $language, &$items) {
                $attributes->each(function ($tr) use ($post, &$items, $language) {
                    $toSave = $this->makeTranslatableItem($post, 'post', "Post $tr", $tr);
                    if ($toSave) {
                        $items->push($toSave);
                    }
                    $post->fieldValues->flatten()->each(function ($fieldValue) use (&$items, $language, $post) {
                        $fieldValue->base_language = $language;
                        $toSave = $this->makeTranslatableItem(
                            $fieldValue,
                            'post_value_' . $fieldValue->attribute->type,
                            "Field '{$fieldValue->attribute->label}' in post {$post->id}",
                            "value"
                        );
                        if ($toSave) {
                            $items->push($toSave);
                        }
                    });
                });
            });
            /**
             * Generates a file per each language containing
             * all the post related entities base text
             */
            $this->generateFile($items->toJson(), $language, 'posts');
            echo OutputText::success("Created file for posts - based on language: $language.");
            echo OutputText::info("Total entities to translate: " . $items->count());
        });
    }
    /**
     * @return Collection
     */
    private function getSurveys()
    {
        $attributes = array_merge(['id', 'base_language'], Survey::translatableAttributes());
        if (!empty($this->exportSurveys)) {
            $surveys = Survey::query()->findMany($this->exportSurveys, $attributes);
        } else {
            $surveys = Survey::all($attributes);
        }
        $surveys = $surveys->makeHidden(['can_create', 'tasks']);
        $surveys = $this->attachProperties($surveys, ['output_type' => 'survey'])
            ->groupBy('base_language');
        return $surveys;
    }

    /**
     * Creates JSON file with the entities relating to Surveys, to be used by translators in their systems
     */
    protected function makeSurveyEntities()
    {
        $surveyAttributes = Collection::make(Survey::translatableAttributes());

        $surveysByLang = $this->getSurveys();
        $surveysByLang->each(function ($surveys, $language) use ($surveyAttributes) {
            if (!$language) {
                return;
            }
            $items = Collection::make([]);
            $surveys->each(function ($survey) use ($surveyAttributes, $language, &$items) {
                // Make translatables for survey attributes
                $surveyAttributes->each(function ($sAttr) use ($survey, &$items, $language) {
                    $toSave = $this->makeTranslatableItem($survey, 'survey', "Survey", $sAttr);
                    if ($toSave) {
                        $items->push($toSave);
                    }
                });
                $stageAttributes = Collection::make(Stage::translatableAttributes());
                $survey->tasks->each(function ($task) use (&$items, $language, $stageAttributes) {
                    $task->base_language = $language;
                    // Make translatables for task/stage attributes
                    $stageAttributes->each(function ($stgAttr) use (&$items, $task) {
                        $toSave = $this->makeTranslatableItem(
                            $task,
                            'task',
                            "Task in survey $task->form_id",
                            $stgAttr
                        );
                        if ($toSave) {
                            $items->push($toSave);
                        }
                    });
                    $attrAttributes = Collection::make(Attribute::translatableAttributes());

                    // Make translatables for the fields inside the task
                    $task->fields->each(function ($attribute) use (&$items, $task, $language, $attrAttributes) {
                        $attribute->base_language = $language;
                        $attrAttributes->each(function ($attrAttr) use ($attribute, &$items, $task) {
                            if ($attribute->type === 'tags') {
                                return;
                            }
                            $toSave = $this->makeTranslatableItem(
                                $attribute,
                                'field',
                                "Field in task $task->id, in survey $task->form_id",
                                $attrAttr
                            );
                            if ($toSave) {
                                $items->push($toSave);
                            }
                        });
                    });
                });
            });
            /**
             * Generates a file per each language containing
             * all the post related entities base text
             */
            $this->generateFile($items->toJson(), $language, 'surveys');
            echo OutputText::success("Created file for surveys - based on language: $language.");
            echo OutputText::info("Total entities to translate: " . $items->count());
        });
    }
    /**
     * @param $entities
     * @param $properties
     * @return Collection
     */
    private function attachProperties($entities, $properties)
    {
        if ($entities instanceof Collection) {
            return $entities->map(function ($entity) use ($properties) {
                return $this->attachProperties($entity, $properties);
            });
        } else {
            $entity = $entities;
            foreach ($properties as $property => $value) {
                $entity->$property = $value;
            }
            return $entity;
        }
    }

    protected function generateFile($json, $language, $type)
    {
        $fprefix = config('media.language_batch_prefix', 'lang');
        $batchprefix = 'batch' . $this->batchStamp;
        $fname = $language . '-' . $type. $this->batchStamp .'-'. Str::random(40) . '.json';
        $filepath = implode(DIRECTORY_SEPARATOR, [
            getenv('CDN_PREFIX'),
            app('multisite')->getSite()->getCdnPrefix(),
            $fprefix,
            $batchprefix,
            $fname,
        ]);

        $stream = tmpfile();
        $fs = service('tool.filesystem');

        /**
         * Before doing anything, clean the ouput buffer and avoid garbage like unnecessary space
         * paddings in our file
         */
        if (ob_get_length()) {
            ob_clean();
        }
        /**
         * Write the JSON to the stream
         */
        fputs($stream, $json);


        // Remove any leading slashes on the filename, path is always relative.
        $filepath = ltrim($filepath, DIRECTORY_SEPARATOR);

        $config = ['mimetype' => 'text/plain'];

        $fs->putStream($filepath, $stream, $config);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $size = $fs->getSize($filepath);
        $type = $fs->getMimetype($filepath);
        echo OutputText::info("Created file $filepath");

        return new FileData([
            'file' => $filepath,
            'type' => $type,
            'size' => $size,
        ]);
    }
}
