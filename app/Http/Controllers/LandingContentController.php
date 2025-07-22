<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LandingContentService;

class LandingContentController extends Controller
{
    protected $landingContentService;

    public function __construct(LandingContentService $landingContentService)
    {
        $this->landingContentService = $landingContentService;
    }
    // =======================
    // 🔹 GENERAL INFO
    // =======================

    public function setGeneralInfo(Request $request)
    {
        $data = $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        try {
            $result = $this->landingContentService->setGeneralInfo($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function updateGeneralInfo(Request $request, $key)
    {
        $data = $request->validate([
            'value' => 'required|string',
        ]);

        try {
            $result = $this->landingContentService->updateGeneralInfo($data, $key);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function getGeneralInfo($key)
    {
        try {
            $info = $this->landingContentService->getGeneralInfo($key);
            return response()->json($info);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
        }
    }

    public function listGeneralInfo()
    {
        return response()->json($this->landingContentService->listGeneralInfo());
    }

    public function deleteGeneralInfo($key)
    {
        try {
            $result = $this->landingContentService->deleteGeneralInfo($key);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], $e->getCode() ?: 404);
        }
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
