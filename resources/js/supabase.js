import { createClient } from '@supabase/supabase-js';

const supabaseUrl = 'https://slqaoorlcirnwmkhilnk.supabase.co';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNscWFvb3JsY2lybndta2hpbG5rIiwicm9sZSI6ImFub24iLCJpYXQiOjE3Mzg0MzUxNzIsImV4cCI6MjA1NDAxMTE3Mn0.NIqvRW3TytCw9z_AzxgDDPrgg7il93DWUJUgYIx5flc';

export const supabase = createClient(supabaseUrl, supabaseKey);
