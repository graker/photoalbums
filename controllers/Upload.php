<?php namespace Graker\PhotoAlbums\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Graker\PhotoAlbums\Models\Album as AlbumModel;
use Graker\PhotoAlbums\Models\Photo as PhotoModel;
use Redirect;
use Backend;
use Flash;
use Input;
use Response;
use System\Models\File;

//TODO remove later on
use Log;

/**
 * Upload Back-end Controller
 */
class Upload extends Controller
{

  /**
   * Display the form
   */
  public function form() {
    $this->pageTitle = 'Upload photos';
    $this->addJs('/modules/backend/assets/vendor/dropzone/dropzone.js');
    $this->addJs('/plugins/graker/photoalbums/assets/js/upload.js');
    $this->addCss('/plugins/graker/photoalbums/assets/css/dropzone.css');
    return $this->makePartial('form');
  }


  /**
   * File upload controller
   */
  public function post_files() {
    if (Input::hasFile('file')) {
      $upload = Input::file('file');

      //TODO add validation
      //TODO add try-catch

      $file = new File;
      $file->data = $upload;
      $file->is_public = true;
      $file->save();

      return Response::json(['id' => $file->id], 200);
    }
  }


  /**
   * Form save callback
   */
  public function onSave() {
    $input = Input::all();
    Log::info($input);
    Flash::success('Photos are saved!');

    $album = AlbumModel::find($input['album']);
    if ($album && !empty($input['file-id'])) {
      Log::info('saving photos');
      $this->savePhotos($album, $input['file-id']);
      return Redirect::to(Backend::url('graker/photoalbums/albums/update/' . $album->id));
    }

    return Redirect::to(Backend::url('graker/photoalbums/albums'));
  }


  /**
   * File remove callback
   */
  public function onFileRemove() {
    if (Input::has('file_id')) {
      $file_id = Input::get('file_id');
      $file = File::find($file_id);
      if ($file) {
        $file->delete();
      }
    }
  }


  /**
   *
   * Saves photos with files attached from $file_ids and attaches them to album
   *
   * @param AlbumModel $album
   * @param array $file_ids
   */
  protected function savePhotos($album, $file_ids) {
    $files = File::whereIn('id', $file_ids)->get();
    $photos = array();
    foreach ($files as $file) {
      $photo = new PhotoModel();
      $photo->save();
      $photo->image()->save($file);
      $photos[] = $photo;
    }
    $album->photos()->saveMany($photos);
  }


  /**
   * @return array of [album id => album title] to use in select list
   */
  protected function getAlbumsList() {
    $albums = AlbumModel::orderBy('created_at', 'desc')->get();
    $options = [];

    foreach ($albums as $album) {
      $options[$album->id] = $album->title;
    }

    return $options;
  }


  public function __construct()
  {
    parent::__construct();

    BackendMenu::setContext('Graker.PhotoAlbums', 'photoalbums', 'upload');
  }
}