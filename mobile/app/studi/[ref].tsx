import { Redirect, useLocalSearchParams } from 'expo-router';

import { studioIdFromRef } from '@/lib/links';

// https://tatlas.app/studi/<id>-<slug> (App Link) → /studio/<id>.
export default function StudioLink() {
  const { ref } = useLocalSearchParams<{ ref: string }>();
  const id = studioIdFromRef(ref);
  return id ? (
    <Redirect href={{ pathname: '/studio/[id]', params: { id } }} />
  ) : (
    <Redirect href="/" />
  );
}
