<?php

declare(strict_types=1);

namespace App;

/**
 * Who is using the frontend, and what their counting is wired to.
 *
 * Three cases:
 *  - a coach's link: a dropdown of the coach and their students, lesson counters;
 *  - a student's link: just themselves, self-practice counters;
 *  - no valid link: the demo, with numbers that live only in the session.
 */
final class Visitor
{
    public const COOKIE_TOKEN = 'hc_token';
    public const COOKIE_CONTEXT = 'hc_ctx';

    /**
     * @param array<string, mixed>|null $coach
     * @param array<string, mixed>|null $student  set only for a student's own link
     * @param list<array{id:int, label:string, kind:string}> $selectable
     * @param array{id:int, label:string, kind:string}|null $selected
     */
    private function __construct(
        public readonly ?array $coach,
        public readonly ?array $student,
        public readonly array $selectable,
        public readonly ?array $selected,
        public readonly CounterStore $counters,
    ) {}

    public function isDemo(): bool
    {
        return $this->coach === null && $this->student === null;
    }

    /** A student practising through their own link. */
    public function isSelfPractice(): bool
    {
        return $this->student !== null;
    }

    /** True when a coach has one of their students selected. */
    public function hasStudentSelected(): bool
    {
        return $this->coach !== null
            && $this->selected !== null
            && $this->selected['kind'] === Contexts::STUDENT;
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
        $context = self::resolveContext($token);

        if ($context === null) {
            return new self(null, null, [], null, new SessionCounterStore());
        }

        if ($context['kind'] === Contexts::STUDENT) {
            $self = [
                'id' => (int) $context['id'],
                'label' => (string) $context['name'],
                'kind' => Contexts::STUDENT,
            ];

            return new self(null, $context, [], $self, new SelfCounterStore((int) $context['id']));
        }

        $selectable = Contexts::selectableFor($context);
        $selected = self::pickSelected($selectable, $contextId);

        // Remember the choice so the next visit opens where they left off.
        Session::setPreference(self::COOKIE_CONTEXT, (string) $selected['id']);

        return new self(
            $context,
            null,
            $selectable,
            $selected,
            new DbCounterStore(
                (int) $context['id'],
                $selected['id'],
                Contexts::rollupIds((int) $context['id']),
            ),
        );
    }

    /**
     * The token may come from the link or from the cookie a previous visit
     * left behind. The link stays in the URL on purpose: it is the credential,
     * and people bookmark it.
     *
     * @return array<string, mixed>|null
     */
    private static function resolveContext(?string $token): ?array
    {
        if ($token !== null) {
            $context = Contexts::findByToken($token);
            if ($context !== null) {
                Session::setPreference(self::COOKIE_TOKEN, $token);

                return $context;
            }

            // An invalid token is treated as no token: demo mode, no error
            // page that would confirm which tokens exist.
            return null;
        }

        $cookie = $_COOKIE[self::COOKIE_TOKEN] ?? null;

        return is_string($cookie) ? Contexts::findByToken($cookie) : null;
    }

    /**
     * @param list<array{id:int, label:string, kind:string}> $selectable
     * @return array{id:int, label:string, kind:string}
     */
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
