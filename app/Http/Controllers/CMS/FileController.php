<?php

namespace Sugar\Http\Controllers\CMS;

use Illuminate\Support\Facades\Auth;
use Sugar\File;
use Sugar\Http\Requests;
use Sugar\Http\Controllers\Controller;
use Response;
use Illuminate\Http\Request;
use View;
use Sugar\User;
use Validator;

class FileController extends CmsController {
	public function __construct(){
		parent::__construct();
		View::share('section', 'files');
	}

	/**
	 * Retrieve all ingredient
	 * @return Collection
	 */

	public function index(){

		$paginator = File::where('user_id', '=', Auth::user()->id)->paginate(20);
		$file_list = collect($paginator->items());
		$data = compact('paginator', 'file_list');
		return view('cms.files.index',$data);
	}

    public function create()
    {
        return view('cms.files.create');
    }

    public function store(Request $request)
    {
        $input = [];
        if($request->file('file')) {
            $file = $request->file('file');
            $validator = Validator::make(
                [
                    'file'      => $file,
                    'extension' => strtolower($file->getClientOriginalExtension()),
                ],
                [
                    'file'          => 'required',
                    'extension'      => 'required|in:csv',
                ]
            );

            if ($validator->fails()) {
                return redirect('admin/files/create')
                    ->withErrors($validator)
                    ->withInput();
            }

            $name = time() . '-' . $file->getClientOriginalName();

            $input['name'] = $name;
            $input['user_id'] = Auth::user()->id;
            $path = public_path() . '/files/upload/';

            // Moves file to folder on server
            $file->move($path, $name);
            $input['name'] = $name;
            $input['user_id'] = Auth::user()->id;
            $result = File::create(['name' => $name,
                 'user_id' => Auth::user()->id]);
            \Session::flash('flash_message', 'File has been uploaded.');
            return redirect('admin/ingredients');

        } else {
            \Session::flash('error_message', 'You have to upload a file');
            return redirect('admin/ingredients/create');
        }
    }
        
        public function delete($file) {
            if(!empty($file->name))
            {
                unlink(User::uploadFilePath() . $file->name);
                File::find($file->id)->delete();
           
                 \Session::flash('flash_message', 'Your File has been deleted');
		 return redirect('admin/files');
            }else {
                \Session::flash('error_message', 'Your file is not deleted');
		return redirect('admin/files');
            }
	}
        
        public function download($file_obj)
        {
            $file= public_path(). "/files/upload/". $file_obj->name;
            $headers = array(
                'Content-Type: application/csv',
                'Content-Disposition:attachment; filename="test.csv"',
                'Content-Transfer-Encoding:binary',
                'Content-Length:'.filesize($file),
            );
            return Response::download($file, 'output.csv');
        }

}