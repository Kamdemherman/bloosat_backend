<?php

namespace App\Traits;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;

trait Loggable
{
    protected static function bootLoggable(): void
    {
        // Only register events that the model actually supports
        $events = ['created', 'updated', 'deleted'];
        
        // Add 'restored' event only if the model uses SoftDeletes
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses(static::class))) {
            $events[] = 'restored';
        }
        
        foreach ($events as $event) {
            static::$event(function ($model) use ($event) {
                SystemLog::create([
                    'user_id'    => Auth::id(),
                    'action'     => $event,
                    'model_type' => class_basename($model),
                    'model_id'   => $model->id,
                    'old_values' => $event === 'updated'
                        ? array_intersect_key($model->getOriginal(), $model->getDirty())
                        : null,
                    'new_values' => in_array($event, ['created', 'updated'])
                        ? $model->getDirty()
                        : null,
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'created_at' => now(),
                ]);
            });
        }
    }
}
