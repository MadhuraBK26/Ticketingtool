<?php

namespace App\Http\Requests\helpdesk;

use App\Http\Requests\Request;

/**
 * InstallerRequest.
 *
 * @author  Ladybird <info@ladybirdweb.com>
 */
class DatabaseRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'default'       => 'required',
            'host'          => 'required',
            'databasename'  => 'required',
            'username'      => 'required',
            // 'password'      =>  '',
            'port'          => 'integer|min:0',
        ];
    }
}
