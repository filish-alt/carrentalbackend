<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GeneralInfo;
use App\Models\Faq;
use App\Models\LandingSection;

class LandingContentController extends Controller
{
    // =======================
    // 🔹 GENERAL INFO
    // =======================

    public function setGeneralInfo(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        $info = GeneralInfo::updateOrCreate(
            ['key' => $request->key],
            ['value' => $request->value]
        );

        return response()->json(['message' => 'Created/Updated', 'data' => $info]);
    }

    public function updateGeneralInfo(Request $request, $key)
    {
        $request->validate([
            'value' => 'required|string',
        ]);

        $info = GeneralInfo::where('key', $key)->first();

        if (! $info) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        $info->value = $request->value;
        $info->save();

        return response()->json(['message' => 'Updated', 'data' => $info]);
    }

    public function getGeneralInfo($key)
    {
        $info = GeneralInfo::where('key', $key)->first();
        return response()->json($info ?? ['message' => 'Key not found'], $info ? 200 : 404);
    }

    public function listGeneralInfo()
    {
        return response()->json(GeneralInfo::all());
    }

    public function deleteGeneralInfo($key)
    {
        $info = GeneralInfo::where('key', $key)->first();

        if (! $info) {
            return response()->json(['message' => 'Key not found'], 404);
        }

        $info->delete();

        return response()->json(['message' => 'Deleted']);
    }

    // =======================
    // 🔹 FAQ
    // =======================

    public function listFaqs()
    {
        return response()->json(Faq::where('is_active', true)->get());
    }

    public function addFaq(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq = Faq::create($request->only(['question', 'answer']));
        return response()->json(['message' => 'FAQ added', 'data' => $faq]);
    }

    public function updateFaq(Request $request, $id)
    {
        $faq = Faq::find($id);

        if (! $faq) {
            return response()->json(['message' => 'FAQ not found'], 404);
        }

        $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        $faq->update($request->only(['question', 'answer']));
        return response()->json(['message' => 'FAQ updated', 'data' => $faq]);
    }

    public function deleteFaq($id)
    {
        Faq::destroy($id);
        return response()->json(['message' => 'FAQ deleted']);
    }

    // =======================
    // 🔹 LANDING SECTIONS
    // =======================

    public function listSections()
    {
        return response()->json(LandingSection::where('is_active', true)->get());
    }

    public function addSection(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'type' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->file('image')?->store('landing_images');

        $section = LandingSection::create([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'image' => $imagePath,
        ]);

        return response()->json(['message' => 'Section added', 'data' => $section]);
    }

    public function updateSection(Request $request, $id)
    {
        $section = LandingSection::find($id);

        if (! $section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        $request->validate([
            'title' => 'required|string',
            'content' => 'nullable|string',
            'type' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('landing_images');
            $section->image = $imagePath;
        }

        $section->title = $request->title;
        $section->content = $request->content;
        $section->type = $request->type;
        $section->save();

        return response()->json(['message' => 'Section updated', 'data' => $section]);
    }

    public function deleteSection($id)
    {
        LandingSection::destroy($id);
        return response()->json(['message' => 'Section deleted']);
    }
}
