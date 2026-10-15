/* Unit 9: Pointers
 * Pointers and arrays - an array name decays to a pointer to its
 * first element, so arr[i] and *(arr + i) are equivalent. */
#include <stdio.h>

int main(void) {
    int arr[5] = {1, 2, 3, 4, 5};

    for (int i = 0; i < 5; i++) {
        printf("arr[%d] = %d, *(arr + %d) = %d\n", i, arr[i], i, *(arr + i));
    }

    return 0;
}
