<?php

declare(strict_types=1);

namespace App;

/**
 * Who is using the frontend, and what their counting is wired to.
 *
 * With a valid access token this is a coach and one selected context row.
 * Without one it is the demo mode: no dropdown, no foreign counters, and
 * numbers that live only in the session.
 */
final class Visitor
{
    public const COOKIE_TOKEN = 'hc_token';
    public const COOKIE_CONTEXT = 'hc_ctx';

    private function __construct(
        public readonly ?array $coach,
        public readonly array $selectable,
        public readonly ?array $selected,
        public readonly CounterStore $counters,
    ) {
    }

    public function isDemo(): bool
    {
        return $this->coach === null;
    }

    /** True when the selected row is a student rather than the coach's own. */
    public function hasStudentSelected(): bool
    {
        return $this->selected !== null && $this->selected['kind'] === Contexts::STUDENT;
    }

    public function coachName(): ?string
    {
        return $this->coach === null ? null : (string) $this->coach['name'];
    }

    /**
     * @param string|null $token      from the query string
     * @param int|null    $contextId  the requested context row
     */
    public static function resolve(?string $token, ?int $contextId): self
    {
        $coach = self::resolveCoach($token);

        if ($coach === null) {
            return new self(null, [], null, new SessionCounterStore());
        }

        $selectable = Contexts::selectableFor($coach);
        $selected = self::pickSelected($selectable, $contextId);

        // Remember the choice so the next visit opens where they left off.
        Session::setPreference(self::COOKIE_CONTEXT, (string) $selected['id']);

        return new self(
            $coach,
            $selectable,
            $selected,
            new DbCounterStore(
                (int) $coach['id'],
                $selected['id'],
                Contexts::rollupIds((int) $coach['id']),
            ),
        );
    }

    /**
     * The token may come from the link or from the cookie a previous visit
     * left behind. The link stays in the URL on purpose: it is the credential,
     * and coaches bookmark it.
     */
    private static function resolveCoach(?string $token): ?array
    {
        if ($token !== null) {
            $coach = Contexts::findCoachByToken($token);
            if ($coach !== null) {
                Session::setPreference(self::COOKIE_TOKEN, $token);

                return $coach;
            }

            // An invalid token is treated as no token: demo mode, no error
            // page that would confirm which tokens exist.
            return null;
        }

        $cookie = $_COOKIE[self::COOKIE_TOKEN] ?? null;

        return is_string($cookie) ? Contexts::findCoachByToken($cookie) : null;
    }

    /** @param array<int, array{id:int, label:string, kind:string}> $selectable */
    private static function pickSelected(array $selectable, ?int $requested): array
    {
        $byId = [];
        foreach ($selectable as $option) {
            $byId[$option['id']] = $option;
        }

        if ($requested !== null && isset($byId[$requested])) {
            return $byId[$requested];
        }

        $remembered = $_COOKIE[self::COOKIE_CONTEXT] ?? null;
        if (is_string($remembered) && isset($byId[(int) $remembered])) {
            return $byId[(int) $remembered];
        }

        // The coach's own row is always first.
        return $selectable[0];
    }
}
