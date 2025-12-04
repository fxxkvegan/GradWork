<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MessageAttachmentService
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function store(Message $message, array $files): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('dm_attachments', 'public');

            $message->attachments()->create([
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize() ?? 0,
            ]);
        }

        if (!$message->has_attachments && $message->attachments()->exists()) {
            $message->forceFill(['has_attachments' => true])->save();
        }
    }

    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
