<?php

namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\Auth;

class MenuObserver
{
    public function creating(Menu $menu): void
    {
        if (empty($menu->created_by)) {
            $menu->created_by = Auth::id();
        }

        if (empty($menu->updated_by)) {
            $menu->updated_by = Auth::id();
        }
    }

    /**
     * Handle the Menu "created" event.
     */
    public function created(Menu $menu): void
    {
        //
    }

    public function updating(Menu $menu): void
    {
        if (empty($menu->updated_by)) {
            $menu->updated_by = Auth::id();
        }
    }

    /**
     * Handle the Menu "updated" event.
     */
    public function updated(Menu $menu): void
    {
        //
    }

    public function deleting(Menu $menu): void
    {
        if (empty($menu->deleted_by)) {
            $menu->deleted_by = Auth::id();
            $menu->save();
        }
    }

    /**
     * Handle the Menu "deleted" event.
     */
    public function deleted(Menu $menu): void
    {
        $menu->children->each(function (Menu $menu) {
            $menu->delete();
        });
    }

    /**
     * Handle the Menu "restored" event.
     */
    public function restored(Menu $menu): void
    {
        //
    }

    /**
     * Handle the Menu "force deleted" event.
     */
    public function forceDeleted(Menu $menu): void
    {
        //
    }
}
