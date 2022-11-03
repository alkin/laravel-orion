<?php

namespace Orion\Tests\Fixtures\App\Http\Requests;

use Orion\Http\Requests\Request;
use Orion\Http\Resources\Resource;

class ProductRequest extends Request
{
    public function rules()
    {
        return [
            'title' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }
}
