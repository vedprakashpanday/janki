<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GreetingTemplate;

class GreetingTemplateController extends Controller
{
    // Templates fetch karna aur default create karna
    public function index()
    {
        $templates = GreetingTemplate::all();

        // Agar templates exist nahi karte, to default wale create kar do
        if ($templates->isEmpty()) {
            $defaults = [
                [
                    'event_type' => 'birthday',
                    'template_text' => "🎉 **Happy Birthday, [Name]!** 🎂🎈\n\nJanki Villa Family ki taraf se aapko janamdin ki dher saari shubhkamnayein! ✨\n\nHumari dua hai ki aapka aane wala har din nayi khushiyan, achhi sehat, aur behisaab kamyabi lekar aaye. Aapke saare sapne pure hon aur yeh saal aapke liye ab tak ka sabse behtareen saal bane!\n\nApna din khul kar enjoy karein! 🥳\n\n**Warm Regards,**\n**Janki Villa** (A Project of Amitabh Builders & Developers Pvt. Ltd.)"
                ],
                [
                    'event_type' => 'anniversary',
                    'template_text' => "💐 **Happy Wedding Anniversary, [Name]!** 🎊🥂\n\nJanki Villa Family ki taraf se aapko aur aapke humsafar ko shadi ki salgirah ki bohot-bohot badhai!\n\nDua hai ki aap dono ka sath hamesha bana rahe, aur aapka aane wala safar pyaar, vishwas, aur khubsurat yaadon se bhara ho. Aap dono ko jivan bhar ki khushiyan aur achhi sehat mile. 🌸\n\nWishing you endless joy and togetherness!\n\n**Warm Regards,**\n**Janki Villa** (A Project of Amitabh Builders & Developers Pvt. Ltd.)"
                ],
                [
                    'event_type' => 'work_anniversary',
                    'template_text' => "🏆 **Happy Work Anniversary, [Name]!** 🚀💼\n\nAaj aapne Janki Villa Family ke sath apne safar ka ek aur shandar saal pura kar liya hai! 🎉\n\nAapki mehnat, lagan aur positive energy humari team ke liye ek badi taqat hai. Pichle samay me aapne jo bhi yogdan diya hai, uske liye hum aapke aabhari hain. Humari kamyabi me aapka bahut bada hissa hai.\n\nAasha hai ki aane wale saalon me hum milkar aur bhi naye milestones achieve karenge! 🌟\n\n**Warm Regards,**\n**Janki Villa** (A Project of Amitabh Builders & Developers Pvt. Ltd.)"
                ]
            ];

            foreach ($defaults as $default) {
                GreetingTemplate::create($default);
            }
            $templates = GreetingTemplate::all();
        }

        return response()->json([
            'success' => true,
            'data' => $templates
        ]);
    }

    // Templates ko update/save karna
    public function store(Request $request)
    {
        $request->validate([
            'templates' => 'required|array',
            'templates.*.event_type' => 'required|string',
            'templates.*.template_text' => 'required|string',
        ]);

        foreach ($request->templates as $tpl) {
            GreetingTemplate::updateOrCreate(
                ['event_type' => $tpl['event_type']],
                ['template_text' => $tpl['template_text']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Templates updated successfully!'
        ]);
    }
}