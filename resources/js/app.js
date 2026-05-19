import './mentions.js';
// Loaded before echo.js so the install coachmark registers even when
// optional services (Reverb/Pusher) fail to initialise in restricted
// environments such as CI browsers without VITE_REVERB_APP_KEY.
import './ios-a2hs.js';
import './echo.js';
import './push.js';
import './sw-register.js';
