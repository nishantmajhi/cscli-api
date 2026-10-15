/* Unit 6: Arrays
 * Multidimensional array: 2D matrix addition. */
#include <stdio.h>

#define ROWS 2
#define COLS 2

int main(void) {
    int a[ROWS][COLS] = {{1, 2}, {3, 4}};
    int b[ROWS][COLS] = {{5, 6}, {7, 8}};
    int sum[ROWS][COLS];

    for (int i = 0; i < ROWS; i++) {
        for (int j = 0; j < COLS; j++) {
            sum[i][j] = a[i][j] + b[i][j];
        }
    }

    printf("Sum matrix:\n");
    for (int i = 0; i < ROWS; i++) {
        for (int j = 0; j < COLS; j++) {
            printf("%d ", sum[i][j]);
        }
        printf("\n");
    }

    return 0;
}
