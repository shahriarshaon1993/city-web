<?php

namespace App\Support\Blocks\Schemas;

use App\Support\Blocks\BlockContext;
use App\Support\Blocks\BlockData;
use App\Support\Blocks\BlockRules;
use App\Support\Blocks\BlockSchema;
use App\Support\Media\MediaKind;

/**
 * Block 1, spec/hero.md §6.2–6.4 (HERO-001…014, 020…023). The headline is the page H1 when the hero
 * opens the page (CMS-PB-004).
 */
final class HeroSchema extends BlockSchema
{
    public function rules(): array
    {
        return [
            'mode' => BlockRules::choice(['single', 'slider']),
            'media_type' => BlockRules::choice(['image', 'video']),
            'image' => [...BlockRules::image(minWidth: 1920), 'required_if:mode,single'],
            'mobile_image' => BlockRules::image(minWidth: 1080),
            'video_mp4' => [...BlockRules::asset(MediaKind::Video), 'required_if:media_type,video'],
            'video_webm' => BlockRules::asset(MediaKind::Video),
            'slides' => ['required_if:mode,slider', 'nullable', 'array', 'max:5'],
            'slides.*.image' => BlockRules::image(true, 1920),
            'slides.*.mobile_image' => BlockRules::image(minWidth: 1080),
            'autoplay' => ['nullable', 'boolean'],
            'eyebrow' => BlockRules::text(40),
            'headline' => BlockRules::text(80, true),
            'subheadline' => BlockRules::text(160),
            ...BlockRules::link('primary_cta'),
            ...BlockRules::link('secondary_cta'),
            // HERO-… minimum 35 while a headline is present (white text stays readable).
            'overlay_opacity' => ['required', 'integer', 'between:35,80', 'multiple_of:5'],
            'overlay_style' => BlockRules::choice(['solid', 'gradient-bottom', 'gradient-left']),
            'text_align' => BlockRules::choice(['left', 'center']),
            'show_search' => ['required', 'boolean'],
            'height' => BlockRules::choice(['full', 'large', 'medium']),
        ];
    }

    public function media(): array
    {
        return ['image', 'mobile_image', 'video_mp4', 'video_webm', 'slides.*.image', 'slides.*.mobile_image'];
    }

    public function attributes(): array
    {
        return [
            'image' => 'hero image', 'mobile_image' => 'mobile image', 'video_mp4' => 'MP4 video', 'video_webm' => 'WebM video',
            'slides' => 'slides', 'slides.*.image' => 'slide image', 'overlay_opacity' => 'overlay strength',
            ...BlockRules::linkAttributes('primary_cta', 'main button'),
            ...BlockRules::linkAttributes('secondary_cta', 'second button'),
        ];
    }

    public function messages(): array
    {
        return [
            'slides.required_if' => 'A slider needs 2 to 5 slides.',
            'image.required_if' => 'Choose the hero image (for a video, its poster).',
            'video_mp4.required_if' => 'Choose the MP4 file for the video.',
        ];
    }

    public function checks(array $data): array
    {
        if (($data['mode'] ?? 'single') === 'slider' && count($data['slides'] ?? []) < 2) {
            return ['slides' => 'A slider needs 2 to 5 slides.'];
        }

        return [];
    }

    public function headline(array $data): ?string
    {
        return $data['headline'] ?? null;
    }

    public function resolve(array $data, BlockContext $context): array
    {
        $slider = ($data['mode'] ?? 'single') === 'slider';
        $mp4 = $context->url($data['video_mp4'] ?? null);
        $showVideo = ! $slider && ($data['media_type'] ?? 'image') === 'video' && $mp4 !== null;
        $showSearch = (bool) ($data['show_search'] ?? false);

        return [
            'mode' => $slider ? 'slider' : 'single',
            'media_type' => $showVideo ? 'video' : 'image',
            'image' => $slider ? null : $context->image($data['image'] ?? null),
            'mobile_image' => $slider ? null : $context->image($data['mobile_image'] ?? null),
            'video' => $showVideo ? ['mp4' => $mp4, 'webm' => $context->url($data['video_webm'] ?? null)] : null,
            'slides' => $slider ? array_map(fn (array $slide): array => [
                'image' => $context->image($slide['image'] ?? null),
                'mobile_image' => $context->image($slide['mobile_image'] ?? null),
            ], $data['slides'] ?? []) : [],
            'autoplay' => $slider && (bool) ($data['autoplay'] ?? false),
            'eyebrow' => $data['eyebrow'] ?? null,
            'headline' => (string) ($data['headline'] ?? ''),
            'subheadline' => $data['subheadline'] ?? null,
            'primary_cta' => $context->link($data['primary_cta'] ?? null),
            'secondary_cta' => $context->link($data['secondary_cta'] ?? null),
            'overlay_opacity' => (int) ($data['overlay_opacity'] ?? 55),
            'overlay_style' => $data['overlay_style'] ?? 'solid',
            'text_align' => $data['text_align'] ?? 'left',
            'show_search' => $showSearch,
            'height' => $data['height'] ?? 'full',
            'search' => $showSearch ? BlockData::projectSearch() : null,
        ];
    }
}
