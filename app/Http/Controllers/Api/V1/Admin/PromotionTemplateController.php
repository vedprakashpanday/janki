<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromotionTemplate;

class PromotionTemplateController extends Controller
{
    // AJAX se template data fetch karne ke liye
    public function getTemplate(Request $request)
    {
        $type = $request->type; 
        $template = PromotionTemplate::where('type', $type)->first();

        return response()->json([
            'status' => 'success',
            'data' => $template
        ]);
    }

    // AJAX se template save karne ke liye
    public function saveTemplate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:employee,member',
            'subject' => 'required|string',
            'template_body' => 'required|string'
        ]);

        $template = PromotionTemplate::updateOrCreate(
            ['type' => $request->type],
            [
                'subject' => $request->subject,
                'template_body' => $request->template_body
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => ucfirst($request->type) . ' Promotion Template saved successfully!'
        ]);
    }
}