/* Unit 7: Functions
 * Passing arrays to a function - computes the average of an array's
 * elements inside a separate function. */
#include <stdio.h>

float average(int arr[], int n) {
    int sum = 0;
    for (int i = 0; i < n; i++) {
        sum += arr[i];
    }
    return (float)sum / n;
}

int main(void) {
    int marks[5] = {80, 90, 75, 60, 95};
    printf("Average = %.2f\n", average(marks, 5));
    return 0;
}
