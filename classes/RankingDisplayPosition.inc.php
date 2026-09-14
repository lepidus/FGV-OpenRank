<?php

class RankingDisplayPosition
{
    const SETTING_NAME = 'displayPosition';
    const SECTION_SETTING_NAME = 'displayPositionSection';

    const TOP = 'top';
    const AFTER_SECTION = 'afterSection';
    const BOTTOM = 'bottom';
    const ADDITIONAL_CONTENT = 'additionalContent';

    const FIRST_SECTION = 1;

    const PLACEHOLDER = '<div class="rankingTabs"></div>';

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
