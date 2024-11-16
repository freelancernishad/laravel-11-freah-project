<?php

namespace App\Http\Controllers\Api\Admin\SocialMedia;

use Illuminate\Http\Request;
use App\Models\SocialMediaLink;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class AdminSocialMediaLinkController extends Controller
{
    /**
     * Store a newly created social media link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:social_media_links,name',
            'url' => 'required|url',
            'icon' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048', // Icon upload validation
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Handle icon upload if provided
        $data = $request->all();
        // Handle icon upload if provided
        if ($request->hasFile('icon')) {

            $iconPath = uploadFileToS3($request->file('icon'),'SocialMediaLink/icon');
            $data['icon'] = $iconPath;
        }

        // Create and store the social media link
        $link = SocialMediaLink::create($data);

        return response()->json($link, 201); // Return the created link
    }

    /**
     * Update the specified social media link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:social_media_links,name,' . $id,
            'url' => 'required|url',
            'icon' => 'nullable|image|mimes:jpg,png,jpeg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Find and update the link
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }
        $data = $request->all();
        // Handle icon upload if provided
        if ($request->hasFile('icon')) {
            $iconPath = uploadFileToS3($request->file('icon'),'SocialMediaLink/icon');
            $data['icon'] = $iconPath;
        }

        $link->update($data);

        return response()->json($link);
    }

    /**
     * Remove the specified social media link.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $link = SocialMediaLink::find($id);

        if (!$link) {
            return response()->json(['message' => 'Link not found'], 404);
        }

        $link->delete();

        return response()->json(['message' => 'Link deleted successfully']);
    }
}
