<?php

namespace Rubel\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Rubel\Repositories\Contracts\PostRepositoryContract;
use Rubel\Models\Post;
use Rubel\Models\Category;
use Rubel\Models\Tag;
use Carbon\Carbon;

class PostRepository implements PostRepositoryContract
{
    /**
     * Post
     *
     * @var Post
     */
    private $postModel;

    /**
     * Category
     *
     * @var Category
     */
    private $categoryModel;

    /**
     * Tag
     *
     * @var Tag
     */
    private $tagModel;

    /**
     * PostRepository constructor
     *
     * @param Post $postModel
     * @param Category $categoryModel
     * @param Tag  $tagModel
     */
    public function __construct(Post $postModel, Category $categoryModel, Tag $tagModel)
    {
        $this->postModel = $postModel;
        $this->categoryModel = $categoryModel;
        $this->tagModel = $tagModel;
    }

    /**
     * Wrap an eloquent with method.
     *
     * @param  array          $relations
     * @return PostRepository
     */
    public function setWith(array $relations): PostRepository
    {
        $this->postModel = $this->postModel->with($relations);

        return $this;
    }

    /**
     * Display a listing of the resource.
     *
     * @param int $paginationLimit
     * @return mixed
     */
    public function findAll(int $paginationLimit = null)
    {
        $posts = $this->postModel->orderBy('created_at', 'desc');

        if ($paginationLimit) {
            return $posts->paginate($paginationLimit);
        }

        return $posts->get();
    }

    /**
     * Display a listing of the resouces.
     *
     * @param int $paginationLimit
     * @return mixed
     */
    public function findPublished(int $paginationLimit = null)
    {
        $posts = $this->postModel->where('publication_status', $this->postModel::PUBLICATION_STATUS_PUBLIC)->orderBy('created_at', 'desc');

        if ($paginationLimit) {
            return $posts->paginate($paginationLimit);
        }

        return $posts->get();
    }

    /**
     * Display the specified resource.
     *
     * @return Post
     */
    public function findLatest(): Post
    {
        return $this->postModel->latest('created_at')->firstOrFail();
    }

    /**
     * Display the listing of the resouces.
     *
     * @param int $paginationLimit
     * @return mixed
     */
    public function findByRandom(int $paginationLimit = null)
    {
        $posts = $this->postModel->where('publication_status', $this->postModel::PUBLICATION_STATUS_PUBLIC)->inRandomOrder()->orderBy('created_at', 'desc');

        if ($paginationLimit) {
            return $posts->paginate($paginationLimit);
        }

        return $posts->get();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Post
     */
    public function findById(int $id): Post
    {
        $post = $this->postModel->findOrFail($id);

        return $post;
    }

    /**
     * Display a listing of the resource.
     *
     * @param  string $name
     * @param  int $paginationLimit
     * @return mixed
     */
    public function findAllByCategoryName(string $name, int $paginationLimit = null)
    {
        $posts = $this->categoryModel->where('name', $name)->firstOrFail()->posts()->where('publication_status', 'public')->orderBy('posts.created_at', 'desc');

        if ($paginationLimit) {
            return $posts->paginate($paginationLimit);
        }

        return $posts->get();
    }

    /**
     * Display a listing of the resource.
     *
     * @param  string $name
     * @param  int $paginationLimit
     * @return mixed
     */
    public function findAllByTagName(string $name, int $paginationLimit = null)
    {
        $posts = $this->tagModel->where('name', $name)->firstOrFail()->posts()->where('publication_status', 'public')->orderBy('posts.created_at', 'desc');

        if ($paginationLimit) {
            return $posts->paginate($paginationLimit);
        }

        return $posts->get();
    }

    /**
     * Display the specified resource.
     *
     * @param  string $title
     * @return Post
     */
    public function findByTitle(string $title): Post
    {
        return $this->postModel->where('title', $title)->where('publication_status', $this->postModel::PUBLICATION_STATUS_PUBLIC)->orderBy('posts.created_at', 'desc')->firstOrFail();
    }

    /**
     * Display the listing of the resources.
     *
     * @param Post $post
     * @param int $paginationLimit
     * @return mixed
     */
    public function findRelatedPost(Post $post, int $paginationLimit = null)
    {
        $posts = $this->postModel->where('posts.id', '!=', $post->id)
                        ->whereHas('tags', function ($query) use ($post) {
                            return $query->whereIn('tags.id', $post->tags()->pluck('tags.id')->toArray());
                        })->orderBy('posts.created_at', 'desc');

        if ($paginationLimit) {
            return $posts->paginate($paginationLimit);
        }

        return $posts->get();
    }

    /**
     * Display the specified resouce.
     *
     * @param  int    $id
     * @return mixed
     */
    public function findPreviousPost(int $id)
    {
        return $this->postModel->where('id', '<', $id)->where('publication_status', 'public')->orderBy('id', 'desc')->first();
    }

    /**
     * Display the specified resource.
     *
     * @param  int    $id
     * @return mixed
     */
    public function findNextPost(int $id)
    {
        return $this->postModel->where('id', '>', $id)->where('publication_status', 'public')->first();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param array $attributes
     * @return Post
     */
    public function store(array $attributes): Post
    {
        $publicationStatus = $attributes['publication_status'];

        $post = $this->postModel->create([
            'admin_id' => 1, // FIXME set authenticated admin id
            'category_id' => $attributes['category_id'],
            'title' => $attributes['title'],
            'md_content' => $attributes['md_content'],
            'html_content' => $attributes['html_content'],
            'publication_status' => $publicationStatus,
            'published_at' => $this->getPublicationDate($publicationStatus),
        ]);

        $this->syncTags($post, $attributes['tags']);

        return $post;
    }

    /**
     * Update the specified resouce in storage.
     *
     * @param int    $id
     * @param array $attributes
     * @return Post
     */
    public function updateById(array $attributes, Int $id): Post
    {
        $post = $this->postModel->findOrFail($id);

        $publicationStatus = $attributes['publication_status'];
        $publicationDate = $post->published_at;

        $post->update([
            'admin_id' => 1, // FIXME set authenticated admin id
            'category_id' => $attributes['category_id'],
            'title' => $attributes['title'],
            'md_content' => $attributes['md_content'],
            'html_content' => $attributes['html_content'],
            'publication_status' => $publicationStatus,
            'published_at' => $this->getPublicationDate($publicationStatus, $publicationDate),
        ]);

        if (count($attributes['tags'])) {
            $this->syncTags($post, $attributes['tags']);
        }

        return $this->postModel->findOrFail($id);
    }

    /**
     * Remove the specified resouce from storage.
     *
     * @return void
     */
    public function destroyById(Int $id): void
    {
        $this->postModel->findOrFail($id)->delete();
    }

    /**
     * Get publication date
     *
     * @param  string  $publicationStatus
     * @param  string  $publicationDate
     * @return string
     */
    private function getPublicationDate($publicationStatus, $publicationDate = null)
    {
        if ($publicationStatus == $this->postModel::PUBLICATION_STATUS_PUBLIC) {
            if (is_null($publicationDate)) {
                return Carbon::now();
            }
        }

        return $publicationDate;
    }

    /**
     * Sync tags.
     *
     * @param Post  $post
     * @param Array $tags
     * @return void
     */
    private function syncTags(Post $post, $tags)
    {
        // HACK
        if ($tags) {
            $requestTagArray = [];

            foreach ($tags as $requestTag) {
                $requestTagArray[] = $requestTag['name'];
            }

            $existTagCollection = $this->tagModel->whereIn('name', $requestTagArray)->get();

            $existTagNameArray = $existTagCollection->pluck('name')->toArray();
            $existTagIdArray = $existTagCollection->pluck('id')->toArray();

            $newTagNameArray = array_diff($requestTagArray, $existTagNameArray);

            if ($newTagNameArray) {
                // Create new tags if there are new tags which has not been registerd.
                foreach ($newTagNameArray as $newTagName) {
                    $this->tagModel->create([
                        'name' => $newTagName,
                    ]);
                }

                $newTagIdArray = $this->tagModel->whereIn('name', $newTagNameArray)->get()->pluck('id')->toArray();

                $tagIdArray = array_merge($existTagIdArray, $newTagIdArray);
            } else {
                $tagIdArray = $existTagIdArray;
            }

            $post->tags()->sync($tagIdArray);
        } else {
            $post->tags()->sync($tags);
        }
    }
}
