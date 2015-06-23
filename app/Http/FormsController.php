<?php
class FormsController extends BaseController {
	protected $input;
	protected $inputRaw;
	protected $type;
    protected $template;
	protected $settings;
	protected $subject;
	protected $from;
	protected $to;
	protected $view;
	protected $validation;
    protected $unset;
    protected $success;
    protected $email_title;

	protected function humanify()
	{
		foreach($this->unset as $unset)
        {
            unset($this->input[$unset]);
        }

		foreach($this->input as $key => $value)
		{
			$newKey = ucwords(str_replace( ['-','_'] , ' ' , $key ));

            unset($this->input[$key]);
			$this->input[$newKey] = $value;
		}
	}

	public function __construct()
	{
		$this->input 	= Input::all();
		$this->inputRaw = $this->input;

        $this->unset = [
            '_token',
            'form-type',
            'email-template'
        ];

		if(is_array($this->inputRaw['phone']))
		{
			$this->inputRaw['phone'] = implode('', $this->inputRaw['phone']);
		}

		$this->type 	= isset($this->input['form-type'])
                                ? $this->input['form-type']
                                : 'contact';

        $this->template = isset($this->input['email-template'])
                                ? $this->input['email-template']
                                : 'generic';

		$this->humanify();

		$this->settings = Setting::asObj();

		$this->subject 	      = $this->settings->{$this->type . '-subject'};
		$this->from 	      = $this->settings->{$this->type . '-from-name'};
		$this->to		      = $this->settings->{$this->type . '-email'};
		$this->success 	      = $this->settings->{$this->type . '-success-message'};
        $this->email_title    = $this->settings->{$this->type . '-email-title'};
		$this->view		      = 'emails.' . $this->template;

		switch($this->type)
		{
			case 'contact':
				$this->validation = [
					'first_name'    => 'required',
                    'last_name'     => 'required',
					'email'         => 'required|email',
					'phone'         => 'required|numeric',
                    'message'       => 'required'
				];

				break;
		}
	}

	public function submit()
	{
		$res = [
			'type'		=>'',
			'message'	=>''
		];

		$validator = Validator::make(
			$this->inputRaw,
			$this->validation
		);

		if ($validator->fails())
		{
		   $res['type'] = 'error';
		   $messages = $validator->messages();

		   foreach($messages->all("\t<p><strong>*</strong> :message</p>\n") as $message)
		   {
				$res['message'] .= $message;
		   }

		} else {
			Mail::send($this->view, ['input' => $this->input,'title' => $this->email_title], function($message)
			{
			    $message->to($this->to, $this->from)->subject($this->subject);
			});

			$res['type'] 	= 'success';
			$res['message']	= $this->success;

		}

		die(json_encode($res));
	}
}
