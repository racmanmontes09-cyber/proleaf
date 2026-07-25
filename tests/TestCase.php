<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Livewire\Component;
use Livewire\Mechanisms\ComponentRegistry;
use PHPUnit\Framework\Assert as PHPUnit;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerLivewireResponseAssertions();
    }

    private function registerLivewireResponseAssertions(): void
    {
        if (TestResponse::hasMacro('assertSeeLivewire')) {
            return;
        }

        TestResponse::macro('assertSeeLivewire', function ($component) {
            if (is_subclass_of($component, Component::class)) {
                $component = app(ComponentRegistry::class)->getName($component);
            }

            $escapedComponentName = trim(htmlspecialchars(json_encode(['name' => $component])), '{}');

            PHPUnit::assertStringContainsString(
                $escapedComponentName,
                $this->getContent(),
                'Cannot find Livewire component ['.$component.'] rendered on page.'
            );

            return $this;
        });
    }
}
