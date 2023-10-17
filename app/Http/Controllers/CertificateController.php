<?php
namespace App\Http\Controllers;
use App\Models\Certificate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;



class CertificateController extends Controller
{
    public function isPremium(Request $request)
{
    $userId = $request->input('userId'); // 'userId' is the query parameter name
    $user = User::findOrFail($userId);

    if ($user->premium == false) {
        return response()->json(['message' => 'Not a Premium User']);
    } else {
        return response()->json(['message' => 'Premium User']);
    }
}


    
    public function makePremium(Request $request)
    {  
        $userId = $request->input('userId'); 
        $user = User::findOrFail($userId);
        if($user->premium == true){
            return response()->json([
                'message' => 'Already Premium User'
            ]);
        }else{
            $user->premium = true;
            $user->premium_start = Carbon::now();
            $user->premium_end = Carbon::now()->subYear();
            $user->save();
            return response()->json([
                'message' => 'Premium Activation Successful'
            ]);
        }

    }

    // Generate a certificate for a user
   public function generateFreeCertificate(Request $request)
   {  
      $userId = $request->input('userId'); 
       $user = User::findOrFail($userId);
       if($user->free_generated < 3 || $user->premium == true){

              // Load the certificate template
       $templatePath = public_path('images/certificate_template.png');
       $certificate = Image::make($templatePath);

       /****
        * Location of the texts are static ie x:160, y:235 so do not alter 
        * 
        */

       $certificate->text("Placeholder Data 1", 160, 235, function ($font) {
           $font->file(public_path('fonts/roboto/Roboto-Black.ttf')); // Font file located at public folder
           $font->size(20); // Adjust the font size as needed
           $font->color('#000000'); // Set the text color
       });
       $certificate->text("Placeholder Data 2", 160, 285, function ($font) {
           $font->file(public_path('fonts/roboto/Roboto-Black.ttf')); 
           $font->size(20);
           $font->color('#000000');
       });
       $certificate->text("Placeholder Data 3", 160, 325, function ($font) {
           $font->file(public_path('fonts/roboto/Roboto-Black.ttf'));
           $font->size(20);
           $font->color('#000000');
       });

        // Set a unique identifier for the certificate (e.g., timestamp)
        $certificateIdentifier = time(); // You can use a different identifier method if needed

        // Generate a unique certificate filename
        $certificatePath = $user->name . $user->id . '_' . $certificateIdentifier . '.png';

        // Use the Storage facade to store the certificate in the public directory
        Storage::disk('public')->put($certificatePath, $certificate->stream()->__toString());

        // Get the storage path of the saved certificate
        $storagePath = storage_path('app/public/' . $certificatePath);

        // Store the certificate in the media library (Spatie)
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


    // Send the generated certificate to a user's email
    public function sendCertificateByEmail(Request $request)
    {
        $userId = $request->input('userId');
        $certificateId = $request->input('certificateId');
        $user = User::findOrFail($userId);
    
        // Construct the certificate path based on the certificate ID
        $certificatePath = $certificateId;
    
        // Check if the certificate folder exists
        if (Storage::disk('public')->exists($certificatePath)) {
            // Check if a certificate with the given ID exists in the user's certificates
            $matchingCertificate = $user->certificates->firstWhere('id', $certificateId);
    
            if ($matchingCertificate) {
                // Check if the user has generated any certificates
                $availableCertificates = $user->media()->where('collection_name', 'certificates')->count();
    
                if ($availableCertificates > 0) {
                //       // Email subject
                // $title = "Congratulations on Generating Your Certificate {$user->name}";
    
                // // Send the email
                // Mail::send('emails.certificate', ['user' => $user], function ($message) use ($user, $title, $matchingCertificate) {
                //     $message
                //         ->to($user->email)
                //         ->subject($title);
    
                //     // Attaching the certificate to the email
                //     $filePath = $matchingCertificate->getPath();
                //     $fileName = 'certificate.png';
    
                //     $message->attach($filePath, [
                //         'as' => $fileName,
                //         'mime' => 'image/png', // Set the mime type for PNG
                //     ]);
                // });
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
    
    
    
    
    

    // function for Certificate Deletion
    public function deleteCertificate(Request $request)
    {
        $userId = $request->input('userId');
        $certificateId = $request->input('certificateId');
        $user = User::findOrFail($userId);
    
        // Construct the certificate path based on the certificate ID
        $certificatePath = $certificateId;
    
        // Check if the certificate folder exists
        if (Storage::disk('public')->exists($certificatePath)) {
            // Check if a certificate with the given ID exists in the user's certificates
            $matchingCertificate = $user->certificates->firstWhere('id', $certificateId);
    
    
            if (Storage::disk('public')->exists($certificatePath)) {
                Storage::disk('public')->delete($certificatePath); // Delete the associated file from storage
                // If it exists in the storage, delete the matching certificate and the file
                $matchingCertificate->delete();
                return response()->json(['message' => 'Certificate deleted successfully']);
            } else {
                // If it doesn't exist in the storage, return an error message
                return response()->json(['message' => 'Certificate file not found in storage']);
            }
        } else {
            return response()->json(['message' => 'Certificate not found or does not belong to the user']);
        }
    }
    
    
}


    


