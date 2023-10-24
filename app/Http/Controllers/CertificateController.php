<?php
namespace App\Http\Controllers;
use App\Models\Certificate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;



class CertificateController extends Controller
{
    public function isPremium(Request $request)
{
    $userId = $request->input('userId');
    $user = User::find($userId);
    if($user === null){
      return response()->json(['message' => 'User does not exist']);
    }
    if ($user->premium == false) {
        return response()->json(['message' => 'Not a Premium User']);
    } else {
        return response()->json(['message' => 'Premium User']);
    }
}


public function revokePremium(Request $request){
    $userId = $request->input('userId');
    $user = User::find($userId);
    if($user->premium == true && now() > $user->premium_end){
        $user->premium = false;
        $user->save();
        return response()->json(['message' => 'Premium Ended']);
    }else{
        return response()->json(['message' => 'Not Premium User or Premium still Active']);

    }
}


    
    public function makePremium(Request $request)
    {  
        $userId = $request->input('userId'); 
        $user = User::find($userId);
        if($user === null){
          return response()->json(['message' => 'User does not exist']);
        }
        if($user->premium == true){
            return response()->json([
                'message' => 'Already Premium User'
            ]);
        }else{
            $user->premium = true;
            $user->premium_start = Carbon::now();
            $user->premium_end = Carbon::now()->addYear(1);
            $user->save();
            return response()->json([
                'message' => 'Premium Activation Successful'
            ]);
        }

    }

    
   public function generateFreeCertificate(Request $request)
   {  
      $name = $request->input('name');
      $title = $request->input('title');
      $course = $request->input('course');

      $userId = $request->input('userId'); 
      $user = User::find($userId);
      if($user === null){
        return response()->json(['message' => 'User does not exist']);
      }
       if($user->free_generated < 3 || $user->premium == true){

        
       $templatePath = public_path('images/certificate_template.png');
       $certificate = Image::make($templatePath);

       /****
        * Location of the texts are static ie x:160, y:235 so do not alter 
        * 
        */

       $certificate->text($name, 160, 235, function ($font) {
           $font->file(public_path('fonts/roboto/Roboto-Black.ttf'));
           $font->size(14);
           $font->color('#000000');
       });
       $certificate->text($title, 160, 285, function ($font) {
           $font->file(public_path('fonts/roboto/Roboto-Black.ttf')); 
           $font->size(14);
           $font->color('#000000');
       });
       $certificate->text($course, 160, 325, function ($font) {
           $font->file(public_path('fonts/roboto/Roboto-Black.ttf'));
           $font->size(14);
           $font->color('#000000');
       });

        $certificateIdentifier = time();

        $certificatePath = $user->name . $user->id . '_' . $certificateIdentifier . '.png';

        Storage::disk('public')->put($certificatePath, $certificate->stream()->__toString());

        $storagePath = storage_path('app/public/' . $certificatePath);

        $user->addMedia($storagePath)->toMediaCollection('certificates');


       $user->free_generated++;
       $user->save();

       Certificate::create([
           'name' => $user->name,
           'userid' => $user->id,
           'email' => $user->email,
           'premium' => $user->premium,
           'certificate_path' => $certificatePath, // Store the path to the certificate
       ]);

       return response()->json(['message' => 'Certificate generated successfully']);
   } else {
       return response()->json(['message' => 'Free generation limit reached']);
   }

    
   }


    public function sendCertificateByEmail(Request $request)
    {
        $userId = $request->input('userId');
        $certificateId = $request->input('certificateId');
        $user = User::find($userId);
        $certificatedata = Certificate::find($certificateId);
        if($certificatedata === null){
            return response()->json(['message' => 'Certificate does not exist']);
        }elseif($user === null){
            return response()->json(['message' => 'User does not exist']);
        }
        $certificatePath = $certificateId;
    
        if (Storage::disk('public')->exists($certificatePath)) {
            $matchingCertificate = $user->certificates->firstWhere('id', $certificateId);
    
            if ($matchingCertificate) {
                $availableCertificates = $user->media()->where('collection_name', 'certificates')->count();
    
                if ($availableCertificates > 0) {
                $title = "Congratulations on Generating Your Certificate {$user->name}";

              $data = [
                'title' => $title,
                'user' => $user,
            ];
    
                Mail::send('emails.certificate', $data, function ($message) use ($user, $title, $matchingCertificate) {
                    $message
                        ->to($user->email)
                        ->subject($title);
    
                    $filePath = $matchingCertificate->getPath();
                    $fileName = 'certificate.png';
    
                    $message->attach($filePath, [
                        'as' => $fileName,
                        'mime' => 'image/png',
                    ]);
                });
                    return response()->json(['message' => 'Certificate sent to email successfully']);
                } else {
                    return response()->json(['message' => 'No available certificates to send']);
                }
            } else {
                return response()->json(['message' => 'Certificate not found or does not belong to the user']);
            }
        } else {
            return response()->json(['message' => 'Certificate not found in storage']);
        }
    }
    
    
    
    
    

    public function deleteCertificate(Request $request)
    {
        $userId = $request->input('userId');
        $certificateId = $request->input('certificateId');
        $user = User::find($userId);
        $certificatedata = Certificate::find($certificateId);
        if($certificatedata === null){
            return response()->json(['message' => 'Certificate does not exist']);
        }elseif($user === null){
            return response()->json(['message' => 'User does not exist']);
        }
        $certificatePath = $certificateId;
    
        if (Storage::disk('public')->exists($certificatePath)) {
            $matchingCertificate = $user->certificates->firstWhere('id', $certificateId);
    
    
            if (Storage::disk('public')->exists($certificatePath)) {
                Storage::disk('public')->delete($certificatePath); 
                $matchingCertificate->delete();
                $certificatedata->delete();
                return response()->json(['message' => 'Certificate deleted successfully']);
            } else {
                return response()->json(['message' => 'Certificate file not found in storage']);
            }
        } else {
            return response()->json(['message' => 'Certificate not found or does not belong to the user']);
        }
    }
    
    
}


    


