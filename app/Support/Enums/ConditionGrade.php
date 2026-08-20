<?php

namespace App\Support\Enums;

enum ConditionGrade: string
{
    case APlusPlus = 'A++';
    case APlus = 'A+';
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case X = 'X';

    /**
     * Minimum listing photo count per the product doc's grading table -
     * lower grades need more photos so buyers can see every flaw upfront.
     */
    public function minimumPhotoCount(): int
    {
        return match ($this) {
            self::APlusPlus, self::APlus => 2,
            self::A, self::B => 3,
            self::C => 4,
            self::D, self::X => 5,
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::APlusPlus => 0,
            self::APlus => 1,
            self::A => 2,
            self::B => 3,
            self::C => 4,
            self::D => 5,
            self::X => 6,
        };
    }

    /**
     * This grade and everything strictly better than it, e.g. A -> [A++, A+, A].
     * Powers the "minimum grade" discovery filter (buyers ask for "A and above").
     *
     * @return list<self>
     */
    public function atLeast(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => $case->rank() <= $this->rank(),
        ));
    }
}
