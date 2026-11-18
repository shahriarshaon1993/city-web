<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use App\Support\Media\MediaKind;
use Illuminate\Validation\Rule;
use UnexpectedValueException;

/**
 * Block 22, CMP-BLK-VIDEO: heading, source* `youtube` (link) or `upload` (MP4 from the library),
 * poster*, WebVTT captions, caption.
 */
final class VideoSchema extends BlockSchema
{
    private const string YOUTUBE = '~(?:v=|youtu\.be/|embed/)([\w-]{11})~';

    public function rules(): array
    {
        return [
            'heading' => BlockRules::text(80),
            'source' => BlockRules::choice(['youtube', 'upload']),
            'url' => ['required_if:source,youtube', 'nullable', 'string', 'max:200', 'regex:'.self::YOUTUBE],
            'video_asset' => [...BlockRules::asset(MediaKind::Video), 'required_if:source,upload'],
            'poster' => BlockRules::image(true),
            'captions' => ['nullable', 'integer', Rule::exists('media_assets', 'id')->whereNull('deleted_at')],
            'caption' => BlockRules::text(200),
        ];
    }

    public function media(): array
    {
        return ['video_asset', 'poster', 'captions'];
    }

    public function attributes(): array
    {
        return ['url' => 'YouTube link', 'video_asset' => 'video file', 'poster' => 'poster image', 'captions' => 'captions file'];
    }

    public function messages(): array
    {
        return ['url.regex' => 'Enter a YouTube link, e.g. https://www.youtube.com/watch?v=…'];
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $title = ($data['heading'] ?? '') !== '' ? $data['heading'] : (($data['caption'] ?? '') !== '' ? $data['caption'] : 'Video');
        $poster = $context->image($data['poster'] ?? null);

        if (($data['source'] ?? 'youtube') === 'upload') {
            $mp4 = $context->url($data['video_asset'] ?? null) ?? throw new UnexpectedValueException('The video file is gone.');

            $video = ['type' => 'upload', 'title' => $title, 'poster' => $poster, 'duration_seconds' => null, 'mp4' => $mp4, 'webm' => null, 'captions' => $context->url($data['captions'] ?? null)];
        } else {
            preg_match(self::YOUTUBE, (string) ($data['url'] ?? ''), $match) === 1 || throw new UnexpectedValueException('Not a YouTube link.');

            $video = ['type' => 'youtube', 'title' => $title, 'poster' => $poster, 'duration_seconds' => null, 'youtube_id' => $match[1]];
        }

        return ['heading' => $data['heading'] ?? null, 'video' => $video, 'caption' => ($data['caption'] ?? '') === '' ? null : $data['caption']];
    }
}
