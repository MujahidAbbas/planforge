<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;

describe('DecimalPosition Service', function () {
    describe('Basic Position Calculations', function () {
        it('returns default gap for empty column', function () {
            $position = DecimalPosition::forEmptyColumn();

            expect($position)->toBe('65535');
        });

        it('calculates position after existing position', function () {
            $position = DecimalPosition::after('65535');

            expect($position)->toBe('131070.0000000000');
        });

        it('calculates position before existing position', function () {
            $position = DecimalPosition::before('65535');

            expect($position)->toBe('0.0000000000');
        });

        it('calculates position between two positions with jitter', function () {
            $after = '65535.0000000000';
            $before = '131070.0000000000';

            $position = DecimalPosition::between($after, $before);

            // Position should be between the two bounds (allowing for jitter)
            expect(DecimalPosition::greaterThan($position, $after))->toBeTrue();
            expect(DecimalPosition::lessThan($position, $before))->toBeTrue();
        });

        it('calculates exact midpoint without jitter', function () {
            $after = '65535.0000000000';
            $before = '131070.0000000000';

            $midpoint = DecimalPosition::betweenExact($after, $before);

            expect($midpoint)->toBe('98302.5000000000');
        });
    });

    describe('Edge Cases', function () {
        it('handles position at top of column with negative result', function () {
            $position = DecimalPosition::before('100.0000000000');

            expect($position)->toBe('-65435.0000000000');
        });

        it('handles very large position values', function () {
            $largePosition = '9999999999.0000000000';
            $after = DecimalPosition::after($largePosition);

            expect(DecimalPosition::greaterThan($after, $largePosition))->toBeTrue();
        });

        it('handles very small gaps between positions', function () {
            $after = '65535.0000000000';
            $before = '65535.0001000000';

            $position = DecimalPosition::between($after, $before);

            expect(DecimalPosition::greaterThan($position, $after))->toBeTrue();
            expect(DecimalPosition::lessThan($position, $before))->toBeTrue();
        });

        it('throws exception when after position is greater than before', function () {
            $after = '131070.0000000000';
            $before = '65535.0000000000';

            expect(fn () => DecimalPosition::between($after, $before))
                ->toThrow(InvalidArgumentException::class);
        });

        it('handles equal positions by appending', function () {
            $position = '65535.0000000000';

            $result = DecimalPosition::between($position, $position);

            expect(DecimalPosition::greaterThan($result, $position))->toBeTrue();
        });

        it('normalizes integer inputs to decimal strings', function () {
            $normalized = DecimalPosition::normalize(65535);

            expect($normalized)->toBe('65535.0000000000');
        });

        it('normalizes float inputs to decimal strings', function () {
            $normalized = DecimalPosition::normalize(65535.5);

            expect($normalized)->toBe('65535.5000000000');
        });

        it('normalizes string inputs to decimal strings', function () {
            $normalized = DecimalPosition::normalize('65535');

            expect($normalized)->toBe('65535.0000000000');
        });
    });

    describe('Sequence Generation', function () {
        it('generates sequential positions for rebalancing', function () {
            $positions = DecimalPosition::generateSequence(3);

            expect($positions)->toHaveCount(3);
            expect($positions[0])->toBe('65535.0000000000');
            expect($positions[1])->toBe('131070.0000000000');
            expect($positions[2])->toBe('196605.0000000000');
        });

        it('generates multiple positions between bounds', function () {
            $after = '0.0000000000';
            $before = '65535.0000000000';

            $positions = DecimalPosition::generateBetween($after, $before, 3);

            expect($positions)->toHaveCount(3);

            // All positions should be between bounds
            foreach ($positions as $position) {
                expect(DecimalPosition::greaterThan($position, $after))->toBeTrue();
                expect(DecimalPosition::lessThan($position, $before))->toBeTrue();
            }

            // Positions should be in ascending order
            expect(DecimalPosition::lessThan($positions[0], $positions[1]))->toBeTrue();
            expect(DecimalPosition::lessThan($positions[1], $positions[2]))->toBeTrue();
        });

        it('returns empty array for zero count', function () {
            $positions = DecimalPosition::generateBetween('0', '65535', 0);

            expect($positions)->toBeEmpty();
        });
    });

    describe('Comparison & Utility', function () {
        it('compares positions correctly', function () {
            $a = '65535.0000000000';
            $b = '131070.0000000000';

            expect(DecimalPosition::compare($a, $b))->toBe(-1);
            expect(DecimalPosition::compare($b, $a))->toBe(1);
            expect(DecimalPosition::compare($a, $a))->toBe(0);
        });

        it('detects when rebalancing is needed', function () {
            $after = '65535.0000000000';
            $before = '65535.0000500000';

            expect(DecimalPosition::needsRebalancing($after, $before))->toBeTrue();
        });

        it('detects when rebalancing is not needed', function () {
            $after = '65535.0000000000';
            $before = '131070.0000000000';

            expect(DecimalPosition::needsRebalancing($after, $before))->toBeFalse();
        });

        it('calculates gap between positions', function () {
            $lower = '65535.0000000000';
            $upper = '131070.0000000000';

            $gap = DecimalPosition::gap($lower, $upper);

            expect($gap)->toBe('65535.0000000000');
        });

        it('checks if position A is less than position B', function () {
            $a = '65535.0000000000';
            $b = '131070.0000000000';

            expect(DecimalPosition::lessThan($a, $b))->toBeTrue();
            expect(DecimalPosition::lessThan($b, $a))->toBeFalse();
        });

        it('checks if position A is greater than position B', function () {
            $a = '131070.0000000000';
            $b = '65535.0000000000';

            expect(DecimalPosition::greaterThan($a, $b))->toBeTrue();
            expect(DecimalPosition::greaterThan($b, $a))->toBeFalse();
        });
    });

    describe('Calculate Method', function () {
        it('returns default position for empty column', function () {
            $position = DecimalPosition::calculate(null, null);

            expect($position)->toBe('65535');
        });

        it('calculates position after card when before is null', function () {
            $afterPos = '65535.0000000000';

            $position = DecimalPosition::calculate($afterPos, null);

            expect(DecimalPosition::greaterThan($position, $afterPos))->toBeTrue();
        });

        it('calculates position before card when after is null', function () {
            $beforePos = '65535.0000000000';

            $position = DecimalPosition::calculate(null, $beforePos);

            expect(DecimalPosition::lessThan($position, $beforePos))->toBeTrue();
        });

        it('calculates position between two cards', function () {
            $afterPos = '65535.0000000000';
            $beforePos = '131070.0000000000';

            $position = DecimalPosition::calculate($afterPos, $beforePos);

            expect(DecimalPosition::greaterThan($position, $afterPos))->toBeTrue();
            expect(DecimalPosition::lessThan($position, $beforePos))->toBeTrue();
        });
    });
});
