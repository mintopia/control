<?php
namespace App\Models\Traits;

trait ToString
{
    public function __toString(): string
    {
        $className = get_called_class();
        $id = $this->id ?? '#';
        $name = '';
        if (method_exists($this, 'toStringName')) {
            $name = ' ' . $this->toStringName();
        }
        return "[{$className}:{$id}]{$name}";
    }
}
