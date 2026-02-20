import './globals/theme.js'; /* By Sheaf.dev */
import './globals/modals.js'; /* By Sheaf.dev */

import '../css/app.css';
import './bootstrap';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

// now you can register
// components using Alpine.data(...) and
// plugins using Alpine.plugin(...)

//slider
import './components/slider.js'

// power grid tables
import './../../vendor/power-components/livewire-powergrid/dist/powergrid'

Livewire.start();
