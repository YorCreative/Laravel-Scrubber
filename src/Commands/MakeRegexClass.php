<?php

namespace YorCreative\Scrubber\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeRegexClass extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:regex-class {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new regex class to use for scrubbing sensitive information.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Command';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/Stubs/RegexCollectionClass.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Scrubber\RegexCollection';
    }
}
