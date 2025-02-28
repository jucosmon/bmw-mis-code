import { supabase } from '@/supabase';
import { onBeforeUnmount, ref } from 'vue';

/**
 * Hook for managing Supabase realtime subscriptions
 * @param {string} channel - Unique channel name for the subscription
 * @param {string} table - Table name to subscribe to
 * @param {Function} callback - Callback function to handle changes
 * @returns {Object} subscription object with unsubscribe method
 */
export function useRealtimeSubscription(channel, table, callback) {
    const subscription = ref(null);
    const status = ref('CLOSED');

    const subscribe = () => {
        try {
            const sub = supabase.channel(channel)
                .on(
                    'postgres_changes',
                    {
                        event: '*',
                        schema: 'public',
                        table: table
                    },
                    (payload) => {
                        console.log(`Realtime event received on ${table}:`, payload);
                        callback({
                            eventType: payload.eventType,
                            new: payload.new,
                            old: payload.old
                        });
                    }
                )
                .subscribe((status) => {
                    console.log(`Subscription status for ${table}:`, status);
                });

            subscription.value = sub;
            status.value = 'SUBSCRIBED';

            return sub;
        } catch (error) {
            console.error(`Error subscribing to ${table}:`, error);
            status.value = 'ERROR';
            return null;
        }
    };

    const unsubscribe = () => {
        if (subscription.value) {
            try {
                supabase.removeChannel(subscription.value);
                status.value = 'CLOSED';
                subscription.value = null;
            } catch (error) {
                console.error(`Error unsubscribing from ${table}:`, error);
            }
        }
    };

    // Initial subscription
    subscribe();

    // Cleanup on unmount
    onBeforeUnmount(() => {
        unsubscribe();
    });

    return {
        subscription,
        status,
        unsubscribe
    };
}
