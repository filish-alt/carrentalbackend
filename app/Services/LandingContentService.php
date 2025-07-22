<?php

namespace App\Services;

use App\Models\GeneralInfo;
use App\Models\Faq;
use App\Models\LandingSection;

class LandingContentService
{
    // General Info Methods
    public function setGeneralInfo(array $data)
    {
        $info = GeneralInfo::updateOrCreate(
            ['key' => $data['key']],
            ['value' => $data['value']]
        );

        return [
            'message' => 'Created/Updated',
            'data' => $info
        ];
    }

    public function updateGeneralInfo(array $data, $key)
    {
        $info = GeneralInfo::where('key', $key)->first();

        if (!$info) {
            throw new \Exception('Key not found', 404);
        }

        $info->value = $data['value'];
        $info->save();

        return [
            'message' => 'Updated',
            'data' => $info
        ];
    }

    public function getGeneralInfo($key)
    {
        $info = GeneralInfo::where('key', $key)->first();
        
        if (!$info) {
            throw new \Exception('Key not found', 404);
        }

        return $info;
    }

    public function listGeneralInfo()
    {
        return GeneralInfo::all();
    }

    public function deleteGeneralInfo($key)
    {
        $info = GeneralInfo::where('key', $key)->first();

        if (!$info) {
            throw new \Exception('Key not found', 404);
        }

        $info->delete();

        return ['message' => 'Deleted'];
    }

    // FAQ Methods
    public function listFaqs()
    {
        return Faq::where('is_active', true)->get();
    }

    public function addFaq(array $data)
    {
        $faq = Faq::create($data);
        
        return [
            'message' => 'FAQ added',
            'data' => $faq
        ];
    }

    public function updateFaq(array $data, $id)
    {
        $faq = Faq::find($id);

        if (!$faq) {
            throw new \Exception('FAQ not found', 404);
        }

        $faq->update($data);
        
        return [
            'message' => 'FAQ updated',
            'data' => $faq
        ];
    }

    public function deleteFaq($id)
    {
        Faq::destroy($id);
        
        return ['message' => 'FAQ deleted'];
    }

    // Landing Section Methods
    public function listSections()
    {
        return LandingSection::where('is_active', true)->get();
    }

    public function addSection(array $data, $request)
    {
        $imagePath = $request->file('image')?->store('landing_images');

        $section = LandingSection::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'type' => $data['type'],
            'image' => $imagePath,
        ]);

        return [
            'message' => 'Section added',
            'data' => $section
        ];
    }

    public function updateSection(array $data, $id, $request)
    {
        $section = LandingSection::find($id);

        if (!$section) {
            throw new \Exception('Section not found', 404);
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('landing_images');
            $section->image = $imagePath;
        }

        $section->title = $data['title'];
        $section->content = $data['content'];
        $section->type = $data['type'];
        $section->save();

        return [
            'message' => 'Section updated',
            'data' => $section
        ];
    }

    public function deleteSection($id)
    {
        LandingSection::destroy($id);
        
        return ['message' => 'Section deleted'];
    }
}
