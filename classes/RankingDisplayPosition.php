<?php

namespace APP\plugins\generic\rankingPlugin\classes;

class RankingDisplayPosition
{
    public const SETTING_NAME = 'displayPosition';
    public const SECTION_SETTING_NAME = 'displayPositionSection';

    public const TOP = 'top';
    public const AFTER_SECTION = 'afterSection';
    public const BOTTOM = 'bottom';
    public const ADDITIONAL_CONTENT = 'additionalContent';

    public const FIRST_SECTION = 1;

    public const PLACEHOLDER = '<div class="rankingTabs"></div>';

    public static function getAll(): array
    {
        return [
            self::TOP,
            self::AFTER_SECTION,
            self::BOTTOM,
            self::ADDITIONAL_CONTENT,
        ];
    }

    public static function normalize($position): string
    {
        if (in_array($position, self::getAll(), true)) {
            return $position;
        }

        return self::ADDITIONAL_CONTENT;
    }

    public static function normalizeSection($section): int
    {
        $section = (int) $section;

        return $section < self::FIRST_SECTION ? self::FIRST_SECTION : $section;
    }

    public static function needsPlaceholder($position): bool
    {
        return self::normalize($position) !== self::ADDITIONAL_CONTENT;
    }
}
