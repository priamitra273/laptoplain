<?php

namespace App\Observers;

use App\Models\User;
use App\Repositories\ProjectRepository;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function __construct(private ProjectRepository $projects) {}

    public function created(Media $media): void
    {
        $this->flushProjectCache($media);
    }

    public function updated(Media $media): void
    {
        $this->flushProjectCache($media);
    }

    public function deleted(Media $media): void
    {
        $this->flushProjectCache($media);
    }

    /**
     * The cached project shell embeds each member's avatar URL, which is backed
     * by a Media row (not a users column), so a User update never covers it.
     */
    private function flushProjectCache(Media $media): void
    {
        if ($media->collection_name === 'avatar' && $media->model_type === (new User)->getMorphClass()) {
            $this->projects->flushShells();
        }
    }
}
