<?php

namespace Tests\Unit;

use App\Models\Nationality;
use App\Services\LookupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupResolverTest extends TestCase
{
    use RefreshDatabase;

    private LookupResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new LookupResolver;
    }

    public function test_empty_input_resolves_to_null(): void
    {
        $this->assertNull($this->resolver->resolve(Nationality::class, null));
        $this->assertNull($this->resolver->resolve(Nationality::class, '   '));
    }

    public function test_creates_lookup_row_when_missing(): void
    {
        $id = $this->resolver->resolve(Nationality::class, 'Тоҷик');

        $this->assertSame($id, Nationality::where('name', 'Тоҷик')->value('id'));
        $this->assertSame(1, Nationality::count());
    }

    public function test_matching_is_case_and_whitespace_insensitive(): void
    {
        $first = $this->resolver->resolve(Nationality::class, 'Тоҷик');
        $second = $this->resolver->resolve(Nationality::class, '  тоҷик  ');

        $this->assertSame($first, $second);
        $this->assertSame(1, Nationality::count());
    }
}
