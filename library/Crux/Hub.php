<?php namespace \library\Crux;

use Validator;
use Input;
use Redirect;
use Hash;

class Hub {
    protected $model;
    protected $files;
    protected $input;
    protected $after;
    protected $except;

    private function hashPasswords()
    {
        if(isset($this->input['password']))
        {
            $this->input['password'] = Hash::make($this->input['password']);
        }
    }

    public function __construct($model, $after = null)
    {
        $class_str      = ucwords($model);

        /***** Defaults Redirects *****/
        $default_after  = (object)[];
        $default_after->success =
        $default_after->error = '/' . str_plural($model);

        /* ==== Common File Names ==== */
        $this->files    = [
                            'thumbnail',
                            'image',
                            'file'
                          ];

        /* ==== Filter Out Meta Inputs  ==== */
        $this->except   = [
                            '_token',
                            '_method',
                            'password_confirmation'
                          ];

        $this->model    = new $class_str;
        $this->input    = array_except(Input::all(), $this->except);
        $this->after    = ($after ? $after : $default_after);

        $this->hashPasswords();
    }

    public function create()
    {
        foreach($this->files as $file)
        {
            if(Input::hasFile($file))
            {
                $temp_file = Input::file($file);
                $name = time() . '-' . $temp_file->getClientOriginalName();
                $temp_file = $temp_file->move(public_path() . '/uploads/',$name);
                $this->input[$file] = $name;
            }
        }

        // Create Entry
        $this->model->create($this->input);

        return Redirect::to($this->after->success);
    }

    public function update($id)
    {
        foreach($this->files as $file)
        {
            if(Input::hasFile($file))
            {
                $temp_file = Input::file($file);
                $name = time() . '-' . $temp_file->getClientOriginalName();
                $temp_file = $temp_file->move(public_path() . '/uploads/', $name);
                $this->input[$file] = $name;
            }
            else
            {
                if(array_key_exists($file,$this->input))
                {
                    unset($this->input[$file]);
                }
            }
        }

        // Modify Entry
        $this->model->find($id)->update($this->input);

        return Redirect::to($this->after->success);
    }
}
