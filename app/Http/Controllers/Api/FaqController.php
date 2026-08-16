<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

class FaqController extends Controller
{
    // 1. Fetch FAQs for DataTables (Filtered by Active/Unanswered)
    public function index(Request $request)
    {
        $status = $request->status ?? 'active';
        
        $faqs = Faq::where('status', $status)
                   ->orderBy('id', 'desc')
                   ->get();

        // Datatables expects data inside a 'data' array
        return response()->json([
            'data' => $faqs
        ]);
    }

    // 2. Save a New FAQ
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'question' => 'required|string',
            'status'   => 'required|in:active,unanswered',
        ]);

        $faq = Faq::create([
            'category'     => $request->category,
            'question'     => $request->question,
            'answer'       => $request->answer,
            'keywords'     => $request->keywords,
            'status'       => $request->status,
            'is_pro_reply' => $request->is_pro_reply ?? 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'FAQ created successfully',
            'data'    => $faq
        ]);
    }

    // 3. Get Single FAQ Data for Editing
    public function show($id)
    {
        $faq = Faq::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data'   => $faq
        ]);
    }

    // 4. Update an Existing FAQ
    public function update(Request $request, $id)
    {
        $request->validate([
            'category' => 'required|string',
            'question' => 'required|string',
            'status'   => 'required|in:active,unanswered',
        ]);

        $faq = Faq::findOrFail($id);
        
        $faq->update([
            'category'     => $request->category,
            'question'     => $request->question,
            'answer'       => $request->answer,
            'keywords'     => $request->keywords,
            'status'       => $request->status,
            'is_pro_reply' => $request->is_pro_reply ?? 0,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'FAQ updated successfully',
            'data'    => $faq
        ]);
    }

    // 5. Delete a FAQ
    public function destroy($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'FAQ deleted successfully'
        ]);
    }
}