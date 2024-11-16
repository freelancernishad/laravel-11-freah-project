<?php

namespace App\Http\Controllers\Api\User\SocialMedia;

use Illuminate\Http\Request;
use App\Models\SocialMediaLink;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class UserSocialMediaLinkController extends Controller
{
    /**
     * Display a listing of the social media links.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $links = SocialMediaLink::all();
        return response()->json($links);
    }

    /**
     * Display the specified social media link.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        return response()->json($link);
    }


}
