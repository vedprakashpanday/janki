<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InterestedCustomer;
use App\Models\Faq;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    // =========================================================================
    // STEP 1: LEAD CAPTURE (With Duplicate Check)
    // =========================================================================
    public function captureLead(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:15'
        ]);

        try {
            // 1. Check if the mobile number already exists in the database
            $existingLead = InterestedCustomer::where('mobile', $request->mobile)->first();

            if ($existingLead) {
                // Agar number pehle se hai, toh naya record mat banao
                // Purani ID ko hi current lead id maan lo
                $leadId = $existingLead->id;
                $replyMsg = "Welcome back {$request->name}! 🙏 Aap kis project ki jankari chahte hain? \n\n1. Plots\n2. Villas\n3. Plots & Villas\n\nAap apna sawal type bhi kar sakte hain.";
            } else {
                // Agar number naya hai, tabhi database me insert karo
                $newLead = InterestedCustomer::create([
                    'cust_name'     => $request->name,
                    'mobile'        => $request->mobile,
                    'company_id'    => 1,
                    'is_member'     => 0,
                    'entry_status'  => 'active',
                    'status'        => 'pending',
                    'provider_name' => 'Website Chatbot'
                ]);

                $leadId = $newLead->id;
                $replyMsg = "Thank you {$request->name}! 🙏 Aap kis project ki jankari chahte hain? \n\n1. Plots\n2. Villas\n3. Plots & Villas\n\nAap apna sawal type bhi kar sakte hain.";
            }

            return response()->json([
                'status' => 'success',
                'lead_id' => $leadId,
                'reply' => $replyMsg
            ]);

        } catch (\Exception $e) {
            Log::error("Lead Capture Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'reply' => 'Kuch technical dikkat aayi hai. Kripya apna sawal type karein.'], 500);
        }
    }

    
    // =========================================================================
    // STEP 2: HANDLE CHAT & KEYWORD MATCHING
    // =========================================================================
    public function handleMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $userMessage = strtolower($request->message);

        // Keyword dhundhne ka logic
        $faq = Faq::where('status', 'active')
            ->where(function($query) use ($userMessage) {
                $words = explode(' ', $userMessage);
                foreach($words as $word) {
                    if(strlen($word) > 2) { // 'ka', 'ki', 'hai' jaise chhote words ko ignore karega
                        $query->orWhere('keywords', 'LIKE', '%' . $word . '%')
                              ->orWhere('question', 'LIKE', '%' . $word . '%');
                    }
                }
            })->first();

        // Agar FAQ mil gaya
        if ($faq) {
            // Case A: Pehle se AI Optimized hai (Bijli ki speed se reply)
            if ($faq->is_pro_reply == 1) {
                return response()->json(['status' => 'success', 'reply' => $faq->answer]);
            } 
            
            // Case B: Raw Entry hai, isko Gemini se optimize karwao (Sirf ek baar)
            else {
                $proAnswer = $this->askGeminiToOptimize($faq->answer, $faq->question);
                
                // Database update kar do taaki agli baar API na call karni pade
                $faq->update([
                    'answer' => $proAnswer,
                    'is_pro_reply' => 1
                ]);

                return response()->json(['status' => 'success', 'reply' => $proAnswer]);
            }
        }

        // Agar FAQ NAHI mila (Naya Sawal)
        // Isko Unanswered me daal do taaki Admin baad me answer de sake
        Faq::create([
            'question'     => $request->message,
            'status'       => 'unanswered',
            'is_pro_reply' => 0
        ]);

        $fallbackReply = "Main apne database me iska jawab nahi dhundh paayi. Lekin chinta na karein, maine aapka number aage forward kar diya hai. Janki Villa ki team aapko subah 10 baje se shaam 7 baje ke beech call karke sabhi details de degi. Kya aap tab tak kisi aur cheez (Plots/Villas) ke baare me janna chahte hain?";

        return response()->json(['status' => 'success', 'reply' => $fallbackReply]);
    }

    // =========================================================================
    // STEP 3: GEMINI FLASH 1.5 INTEGRATION
    // =========================================================================
    private function askGeminiToOptimize($rawAnswer, $question)
    {
        $geminiApiKey = env('GEMINI_API_KEY');
        
        $prompt = "You are Neha, a highly professional, polite, and welcoming real estate AI assistant for 'Janki Villa' township by Amitabh Builders in Darbhanga, Bihar. 
                   A user asked: '{$question}'. 
                   The raw facts are: '{$rawAnswer}'.
                   Rewrite these raw facts into a polite, engaging, and highly professional response in Hinglish (Hindi + English). 
                   Rules: 
                   1. Do NOT add any extra details, prices, or fake facts. Use ONLY the raw facts provided.
                   2. Keep it concise, friendly, and easy to read.
                   3. Start with a polite greeting if appropriate.
                   4. Do not include markdown bold formatting like **text** unless absolutely necessary for numbers.";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiApiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
        } catch (\Exception $e) {
            Log::error("Gemini AI API Error: " . $e->getMessage());
        }

        // Agar by-chance Google ki API fail hui ya limit cross hui, toh raw answer hi bhej do
        return $rawAnswer; 
    }
}