/* Unit 6: Arrays
 * Single dimensional array: declaration, initialization, reading,
 * and finding the largest element. */
#include <stdio.h>

int main(void) {
    int arr[10], n;

    printf("Enter number of elements (max 10): ");
    scanf("%d", &n);

    printf("Enter %d elements: ", n);
    for (int i = 0; i < n; i++) {
        scanf("%d", &arr[i]);
    }

    int max = arr[0];
    for (int i = 1; i < n; i++) {
        if (arr[i] > max) {
            max = arr[i];
        }
    }

    printf("Largest element = %d\n", max);
    return 0;
}
